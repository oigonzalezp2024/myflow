package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os/exec"
	"strings"
	"time"
)

type Response struct {
	Success bool   `json:"success"`
	Message string `json:"message"`
	Details string `json:"details,omitempty"`
}

type BaseRequest struct {
	ContainerName string `json:"container_name"`
	RootPassword  string `json:"root_password"`
	DbName        string `json:"db_name"`
	Port          string `json:"port"`
	ServerID      int    `json:"server_id"`
	ReplUser      string `json:"repl_user"`
	ReplPassword  string `json:"repl_password"`
}

type MasterRequest struct {
	BaseRequest
}

type ReplicaRequest struct {
	BaseRequest
	MasterName  string `json:"master_name"`
	ReplicaName string `json:"replica_name"`
}

func runCommand(name string, args ...string) error {
	cmd := exec.Command(name, args...)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("error ejecutando %s %v: %v\nSalida: %s", name, args, err, string(output))
	}
	return nil
}

func runCommandOutput(name string, args ...string) (string, error) {
	cmd := exec.Command(name, args...)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return "", fmt.Errorf("error ejecutando %s %v: %v\nSalida: %s", name, args, err, string(output))
	}
	return string(output), nil
}

// Función unificada y robusta para desplegar cualquier nodo MySQL (Maestro o Réplica)
func deployMySQLContainer(req BaseRequest, extraArgs ...string) error {
	// 1. Asegurar la red compartida
	_ = runCommand("docker", "network", "create", "mysql-net")
	
	// 2. Limpieza controlada específica del contenedor actual para permitir múltiples instancias
	_ = runCommand("docker", "rm", "-f", "-v", req.ContainerName)

	args := []string{
		"run", "--name", req.ContainerName,
		"--net", "mysql-net",
		"-p", fmt.Sprintf("%s:3306", req.Port),
		"-e", fmt.Sprintf("MYSQL_ROOT_PASSWORD=%s", req.RootPassword),
		"-e", fmt.Sprintf("MYSQL_DATABASE=%s", req.DbName),
		"-d", "mysql:8.0",
		// Se elimina el --server-id duplicado de aquí. Se pasa exclusivamente por extraArgs.
	}
	args = append(args, extraArgs...)

	if err := runCommand("docker", args...); err != nil {
		return fmt.Errorf("fallo al iniciar el contenedor: %w", err)
	}

	// 3. Esperar a que MySQL esté disponible por TCP (evita el falso positivo del socket local)
	const maxAttempts = 40
	var lastErr error

	for attempt := 1; attempt <= maxAttempts; attempt++ {
		lastErr = runCommand(
			"docker", "exec", req.ContainerName,
			"mysql",
			"-h", "127.0.0.1",
			"-P", "3306",
			"-uroot",
			fmt.Sprintf("-p%s", req.RootPassword),
			"-e", "SELECT 1",
		)

		if lastErr == nil {
			break
		}

		time.Sleep(3 * time.Second)
	}

	if lastErr != nil {
		return fmt.Errorf("MySQL no estuvo disponible por TCP tras %d intentos: %w", maxAttempts, lastErr)
	}

	// 4. Garantizar la existencia y configuración de la base de datos
	createDbSQL := fmt.Sprintf("CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;", req.DbName)
	if err := runCommand("docker", "exec", "-i", req.ContainerName, "mysql", "-uroot", fmt.Sprintf("-p%s", req.RootPassword), "-e", createDbSQL); err != nil {
		return fmt.Errorf("fallo al crear la base de datos inicial: %w", err)
	}

	return nil
}

// Middleware común para validar métodos HTTP y cabeceras CORS básicas
func handleRequest(w http.ResponseWriter, r *http.Request, handlerFunc func(http.ResponseWriter, *http.Request)) {
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Methods", "PUT, OPTIONS")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type")

	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}

	if r.Method != http.MethodPut {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusMethodNotAllowed)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Método no permitido. Utiliza PUT."})
		return
	}

	handlerFunc(w, r)
}

func applyDefaultsBase(req *BaseRequest) {
	if req.RootPassword == "" { req.RootPassword = "rootpassword" }
	if req.DbName == "" { req.DbName = "testdb" }
	if req.ReplUser == "" { req.ReplUser = "repl" }
	if req.ReplPassword == "" { req.ReplPassword = "replpassword" }
}

func setupMasterHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Access-Control-Expose-Headers", "X-Binlog-File, X-Binlog-Pos")

	var req MasterRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Estructura JSON inválida", Details: err.Error()})
		return
	}
	applyDefaultsBase(&req.BaseRequest)

	if req.ContainerName == "" {
		req.ContainerName = "mysql-master"
	}
	if req.Port == "" {
		req.Port = "3306"
	}
	if req.ServerID == 0 {
		req.ServerID = 1
	}

	log.Printf("Desplegando nodo Master: %s...\n", req.ContainerName)

	extraArgs := []string{
		fmt.Sprintf("--server-id=%d", req.ServerID),
		"--log-bin=mysql-bin",
		"--binlog-format=ROW",
	}

	err := deployMySQLContainer(req.BaseRequest, extraArgs...)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Fallo al desplegar el contenedor Master", Details: err.Error()})
		return
	}

	createReplUserSQL := fmt.Sprintf(
		"CREATE USER IF NOT EXISTS '%s'@'%%' IDENTIFIED WITH mysql_native_password BY '%s'; "+
			"GRANT REPLICATION SLAVE ON *.* TO '%s'@'%%'; "+
			"FLUSH PRIVILEGES;",
		req.ReplUser, req.ReplPassword, req.ReplUser,
	)

	err = runCommand("docker", "exec", "-i", req.ContainerName, "mysql", "-uroot", fmt.Sprintf("-p%s", req.RootPassword), "-e", createReplUserSQL)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Fallo al crear el usuario de replicación en el Master", Details: err.Error()})
		return
	}

	showMasterStatus, err := runCommandOutput("docker", "exec", "-i", req.ContainerName, "mysql", "-uroot", fmt.Sprintf("-p%s", req.RootPassword), "-N", "-e", "SHOW MASTER STATUS;")
	if err == nil {
		fields := strings.Fields(showMasterStatus)
		if len(fields) >= 2 {
			w.Header().Set("X-Binlog-File", fields[0])
			w.Header().Set("X-Binlog-Pos", fields[1])
		}
	}

	w.Header().Set("Content-Type", "application/json")
	log.Printf("¡Master %s desplegado y configurado correctamente!\n", req.ContainerName)
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(Response{
		Success: true,
		Message: fmt.Sprintf("Contenedor Master %s desplegado y usuario de replicación configurado con éxito.", req.ContainerName),
	})
}

func setupReplicaHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Access-Control-Expose-Headers", "X-Binlog-File, X-Binlog-Pos")

	var req ReplicaRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Estructura JSON inválida", Details: err.Error()})
		return
	}
	applyDefaultsBase(&req.BaseRequest)

	if req.ReplicaName == "" {
		req.ReplicaName = fmt.Sprintf("mysql-replica-%d", time.Now().Unix())
	}
	req.ContainerName = req.ReplicaName

	if req.MasterName == "" { req.MasterName = "mysql-master" }
	if req.Port == "" { req.Port = "3307" }
	if req.ServerID == 0 { req.ServerID = int(time.Now().Unix()%1000) + 10 }

	log.Printf("Desplegando réplica: %s vinculada a %s...\n", req.ReplicaName, req.MasterName)

	extraArgs := []string{
		fmt.Sprintf("--server-id=%d", req.ServerID),
	}

	err := deployMySQLContainer(req.BaseRequest, extraArgs...)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Fallo al desplegar la réplica base", Details: err.Error()})
		return
	}

	showMasterStatus, err := runCommandOutput("docker", "exec", "-i", req.MasterName, "mysql", "-uroot", fmt.Sprintf("-p%s", req.RootPassword), "-N", "-e", "SHOW MASTER STATUS;")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Fallo al obtener estado del Master (Binlog)", Details: err.Error()})
		return
	}

	var cleanLines []string
	for _, line := range strings.Split(showMasterStatus, "\n") {
		trimmed := strings.TrimSpace(line)
		if trimmed != "" && !strings.HasPrefix(trimmed, "mysql:") {
			cleanLines = append(cleanLines, trimmed)
		}
	}

	if len(cleanLines) == 0 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "No se pudo leer la información del Binlog del Master"})
		return
	}

	fields := strings.Fields(cleanLines[0])
	binlogFile := fields[0]
	binlogPos := fields[1]

	replicationSQL := fmt.Sprintf(
		"STOP SLAVE; "+
			"CHANGE MASTER TO "+
			"MASTER_HOST='%s', "+
			"MASTER_USER='%s', "+
			"MASTER_PASSWORD='%s', "+
			"MASTER_LOG_FILE='%s', "+
			"MASTER_LOG_POS=%s, "+
			"GET_MASTER_PUBLIC_KEY=1; "+
			"START SLAVE;",
		req.MasterName, req.ReplUser, req.ReplPassword, binlogFile, binlogPos,
	)

	err = runCommand("docker", "exec", "-i", req.ContainerName, "mysql", "-uroot", fmt.Sprintf("-p%s", req.RootPassword), "-e", replicationSQL)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Fallo al vincular la réplica con el Master", Details: err.Error()})
		return
	}

	w.Header().Set("X-Binlog-File", binlogFile)
	w.Header().Set("X-Binlog-Pos", binlogPos)
	w.Header().Set("Content-Type", "application/json")

	log.Printf("¡Réplica %s vinculada correctamente con Master (%s)!\n", req.ReplicaName, req.MasterName)
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(Response{
		Success: true,
		Message: fmt.Sprintf("Réplica %s sincronizada y vinculada correctamente al Master.", req.ReplicaName),
	})
}

func main() {
	http.HandleFunc("/replication/master", func(w http.ResponseWriter, r *http.Request) {
		handleRequest(w, r, setupMasterHandler)
	})
	http.HandleFunc("/replication/replica", func(w http.ResponseWriter, r *http.Request) {
		handleRequest(w, r, setupReplicaHandler)
	})

	port := ":8080"
	fmt.Printf("Servidor HTTP escuchando en http://localhost%s\n", port)
	log.Fatal(http.ListenAndServe(port, nil))
}
