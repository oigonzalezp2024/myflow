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
	Success    bool   `json:"success"`
	Message    string `json:"message"`
	Details    string `json:"details,omitempty"`
	BinlogFile string `json:"binlog_file,omitempty"`
	BinlogPos  string `json:"binlog_pos,omitempty"`
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

func extractCleanLines(raw string) []string {
	var cleanLines []string
	for _, line := range strings.Split(raw, "\n") {
		trimmed := strings.TrimSpace(line)
		if trimmed != "" && !strings.HasPrefix(trimmed, "mysql:") {
			cleanLines = append(cleanLines, trimmed)
		}
	}
	return cleanLines
}

func getMasterBinlogStatus(masterName, rootPassword string) (string, string, error) {
	showMasterStatus, err := runCommandOutput(
		"docker", "exec", "-i", masterName,
		"mysql", "-uroot", fmt.Sprintf("-p%s", rootPassword),
		"-N", "-e", "SHOW MASTER STATUS;",
	)
	if err != nil {
		return "", "", fmt.Errorf("fallo al consultar estado del Master %s: %w", masterName, err)
	}

	cleanLines := extractCleanLines(showMasterStatus)
	if len(cleanLines) == 0 {
		return "", "", fmt.Errorf("el Master %s no devolvió filas en SHOW MASTER STATUS (verifique que log-bin esté activo)", masterName)
	}

	fields := strings.Fields(cleanLines[0])
	if len(fields) < 2 {
		return "", "", fmt.Errorf("salida inesperada de SHOW MASTER STATUS en %s: %q", masterName, cleanLines[0])
	}

	return fields[0], fields[1], nil
}

func waitForMySQLReady(containerName, rootPassword string) error {
	const maxAttempts = 40
	var lastErr error

	for attempt := 1; attempt <= maxAttempts; attempt++ {
		runningOut, inspectErr := runCommandOutput("docker", "inspect", "-f", "{{.State.Running}}", containerName)
		if inspectErr != nil || strings.TrimSpace(runningOut) != "true" {
			return fmt.Errorf("el contenedor %s no está en ejecución (estado: %q, error: %v)", containerName, strings.TrimSpace(runningOut), inspectErr)
		}

		lastErr = runCommand(
			"docker", "exec", containerName,
			"mysql",
			"-h", "127.0.0.1",
			"-P", "3306",
			"-uroot",
			fmt.Sprintf("-p%s", rootPassword),
			"-e", "SELECT 1",
		)

		if lastErr == nil {
			return nil
		}

		time.Sleep(3 * time.Second)
	}

	return fmt.Errorf("MySQL en %s no estuvo disponible por TCP tras %d intentos: %w", containerName, maxAttempts, lastErr)
}

// Función unificada y robusta para desplegar cualquier nodo MySQL (Maestro o Réplica)
func deployMySQLContainer(req BaseRequest, extraArgs ...string) error {
	// 1. Asegurar la red compartida
	if err := runCommand("docker", "network", "create", "mysql-net"); err != nil {
		// Si ya existe, docker network create devuelve error; verificamos que exista
		if inspectErr := runCommand("docker", "network", "inspect", "mysql-net"); inspectErr != nil {
			return fmt.Errorf("no se pudo asegurar la red mysql-net: %w", err)
		}
	}

	// 2. Limpieza controlada específica del contenedor actual para permitir múltiples instancias
	_ = runCommand("docker", "rm", "-f", "-v", req.ContainerName)

	args := []string{
		"run", "--name", req.ContainerName,
		"--net", "mysql-net",
		"-p", fmt.Sprintf("%s:3306", req.Port),
		"-e", fmt.Sprintf("MYSQL_ROOT_PASSWORD=%s", req.RootPassword),
		"-e", fmt.Sprintf("MYSQL_DATABASE=%s", req.DbName),
		"-d", "mysql:8.0",
	}
	args = append(args, extraArgs...)

	if err := runCommand("docker", args...); err != nil {
		return fmt.Errorf("fallo al iniciar el contenedor: %w", err)
	}

	// 3. Esperar a que MySQL esté disponible por TCP
	if err := waitForMySQLReady(req.ContainerName, req.RootPassword); err != nil {
		return err
	}

	// 4. Garantizar la existencia y configuración de la base de datos
	createDbSQL := fmt.Sprintf("CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;", req.DbName)
	if err := runCommand("docker", "exec", "-i", req.ContainerName, "mysql", "-uroot", fmt.Sprintf("-p%s", req.RootPassword), "-e", createDbSQL); err != nil {
		return fmt.Errorf("fallo al crear la base de datos inicial: %w", err)
	}

	return nil
}

func checkReplicaStatus(replicaName, rootPassword string) (string, error) {
	time.Sleep(2 * time.Second)

	out, err := runCommandOutput(
		"docker", "exec", "-i", replicaName,
		"mysql", "-uroot", fmt.Sprintf("-p%s", rootPassword),
		"-e", "SHOW REPLICA STATUS\\G",
	)
	if err != nil {
		return "", fmt.Errorf("no se pudo consultar SHOW REPLICA STATUS en %s: %w", replicaName, err)
	}

	ioRunning := ""
	sqlRunning := ""
	lastIOError := ""
	lastSQLError := ""

	for _, line := range strings.Split(out, "\n") {
		trimmed := strings.TrimSpace(line)
		switch {
		case strings.HasPrefix(trimmed, "Replica_IO_Running:"):
			ioRunning = strings.TrimSpace(strings.TrimPrefix(trimmed, "Replica_IO_Running:"))
		case strings.HasPrefix(trimmed, "Replica_SQL_Running:"):
			sqlRunning = strings.TrimSpace(strings.TrimPrefix(trimmed, "Replica_SQL_Running:"))
		case strings.HasPrefix(trimmed, "Last_IO_Error:"):
			lastIOError = strings.TrimSpace(strings.TrimPrefix(trimmed, "Last_IO_Error:"))
		case strings.HasPrefix(trimmed, "Last_SQL_Error:"):
			lastSQLError = strings.TrimSpace(strings.TrimPrefix(trimmed, "Last_SQL_Error:"))
		}
	}

	summary := fmt.Sprintf("IO=%s, SQL=%s", ioRunning, sqlRunning)
	if ioRunning != "Yes" || sqlRunning != "Yes" {
		return summary, fmt.Errorf("la replicación no quedó activa (%s). Last_IO_Error=%q | Last_SQL_Error=%q", summary, lastIOError, lastSQLError)
	}

	return summary, nil
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
	if req.RootPassword == "" {
		req.RootPassword = "rootpassword"
	}
	if req.DbName == "" {
		req.DbName = "testdb"
	}
	if req.ReplUser == "" {
		req.ReplUser = "repl"
	}
	if req.ReplPassword == "" {
		req.ReplPassword = "replpassword"
	}
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

	if err := deployMySQLContainer(req.BaseRequest, extraArgs...); err != nil {
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

	if err := runCommand("docker", "exec", "-i", req.ContainerName, "mysql", "-uroot", fmt.Sprintf("-p%s", req.RootPassword), "-e", createReplUserSQL); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Fallo al crear el usuario de replicación en el Master", Details: err.Error()})
		return
	}

	binlogFile, binlogPos, err := getMasterBinlogStatus(req.ContainerName, req.RootPassword)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Master desplegado, pero falló la lectura del estado del Binlog", Details: err.Error()})
		return
	}

	w.Header().Set("X-Binlog-File", binlogFile)
	w.Header().Set("X-Binlog-Pos", binlogPos)
	w.Header().Set("Content-Type", "application/json")

	log.Printf("¡Master %s desplegado y configurado correctamente! (%s:%s)\n", req.ContainerName, binlogFile, binlogPos)
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(Response{
		Success:    true,
		Message:    fmt.Sprintf("Contenedor Master %s desplegado y usuario de replicación configurado con éxito.", req.ContainerName),
		BinlogFile: binlogFile,
		BinlogPos:  binlogPos,
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

	if req.MasterName == "" {
		req.MasterName = "mysql-master"
	}
	if req.Port == "" {
		req.Port = "3307"
	}
	if req.ServerID == 0 {
		req.ServerID = int(time.Now().Unix()%1000) + 10
	}

	// 1. Verificar el Master y obtener coordenadas del binlog antes de crear la réplica
	binlogFile, binlogPos, err := getMasterBinlogStatus(req.MasterName, req.RootPassword)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(Response{
			Success: false,
			Message: "No se puede crear la réplica porque el Master no está listo o no tiene binlog activo",
			Details: err.Error(),
		})
		return
	}

	log.Printf("Desplegando réplica: %s vinculada a %s (%s:%s)...\n", req.ReplicaName, req.MasterName, binlogFile, binlogPos)

	extraArgs := []string{
		fmt.Sprintf("--server-id=%d", req.ServerID),
	}

	if err := deployMySQLContainer(req.BaseRequest, extraArgs...); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Fallo al desplegar la réplica base", Details: err.Error()})
		return
	}

	replicationSQL := fmt.Sprintf(
		"STOP REPLICA; "+
			"CHANGE REPLICATION SOURCE TO "+
			"SOURCE_HOST='%s', "+
			"SOURCE_USER='%s', "+
			"SOURCE_PASSWORD='%s', "+
			"SOURCE_LOG_FILE='%s', "+
			"SOURCE_LOG_POS=%s, "+
			"GET_SOURCE_PUBLIC_KEY=1; "+
			"START REPLICA;",
		req.MasterName, req.ReplUser, req.ReplPassword, binlogFile, binlogPos,
	)

	if err := runCommand("docker", "exec", "-i", req.ContainerName, "mysql", "-uroot", fmt.Sprintf("-p%s", req.RootPassword), "-e", replicationSQL); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{Success: false, Message: "Fallo al vincular la réplica con el Master", Details: err.Error()})
		return
	}

	statusSummary, err := checkReplicaStatus(req.ContainerName, req.RootPassword)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(Response{
			Success:    false,
			Message:    "La réplica se creó, pero los hilos de replicación no están activos",
			Details:    err.Error(),
			BinlogFile: binlogFile,
			BinlogPos:  binlogPos,
		})
		return
	}

	w.Header().Set("X-Binlog-File", binlogFile)
	w.Header().Set("X-Binlog-Pos", binlogPos)
	w.Header().Set("Content-Type", "application/json")

	log.Printf("¡Réplica %s vinculada y activa con Master (%s) [%s]!\n", req.ReplicaName, req.MasterName, statusSummary)
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(Response{
		Success:    true,
		Message:    fmt.Sprintf("Réplica %s sincronizada y vinculada correctamente al Master (%s).", req.ReplicaName, statusSummary),
		BinlogFile: binlogFile,
		BinlogPos:  binlogPos,
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
