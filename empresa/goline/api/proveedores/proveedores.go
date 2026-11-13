package main

import (
	"database/sql"
	"encoding/json"
	"log"
	"net/http"
	"os"
	"strconv"
	"strings"

	_ "github.com/go-sql-driver/mysql"
	"github.com/joho/godotenv"
)

// ==========================================
// 1. DOMAIN: Entidad Proveedor
// ==========================================
type Proveedor struct {
	ID                 int    `json:"id_proveedor,omitempty" db:"id_proveedor"`
	ProveedorNombre    string `json:"proveedor_nombre,omitempty" db:"proveedor_nombre"`
	ProveedorDireccion string `json:"proveedor_direccion,omitempty" db:"proveedor_direccion"`
}

// ==========================================
// 2. INFRASTRUCTURE: Repositorio MySQL
// ==========================================
type ProveedorRepository struct {
	db *sql.DB
}

func NewProveedorRepository(db *sql.DB) *ProveedorRepository {
	return &ProveedorRepository{db: db}
}

func (r *ProveedorRepository) GetAll() ([]Proveedor, error) {
	rows, err := r.db.Query("SELECT id_proveedor, proveedor_nombre, proveedor_direccion FROM proveedores")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var proveedores []Proveedor
	for rows.Next() {
		var p Proveedor
		if err := rows.Scan(&p.ID, &p.ProveedorNombre, &p.ProveedorDireccion); err != nil {
			continue
		}
		proveedores = append(proveedores, p)
	}
	return proveedores, nil
}

func (r *ProveedorRepository) GetByID(id int) (*Proveedor, error) {
	row := r.db.QueryRow("SELECT id_proveedor, proveedor_nombre, proveedor_direccion FROM proveedores WHERE id_proveedor = ?", id)
	var p Proveedor
	err := row.Scan(&p.ID, &p.ProveedorNombre, &p.ProveedorDireccion)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

func (r *ProveedorRepository) Create(p Proveedor) (int64, error) {
	result, err := r.db.Exec(
		"INSERT INTO `proveedores` (`proveedor_nombre`, `proveedor_direccion`) VALUES (?, ?)",
		p.ProveedorNombre, p.ProveedorDireccion,
	)
	if err != nil {
		return 0, err
	}
	return result.LastInsertId()
}

func (r *ProveedorRepository) Update(id int, p Proveedor) (bool, error) {
	result, err := r.db.Exec(
		"UPDATE `proveedores` SET `proveedor_nombre` = ?, `proveedor_direccion` = ? WHERE `id_proveedor` = ?",
		p.ProveedorNombre, p.ProveedorDireccion, id,
	)
	if err != nil {
		return false, err
	}
	rowsAffected, err := result.RowsAffected()
	return rowsAffected > 0, err
}

func (r *ProveedorRepository) Delete(id int) (bool, error) {
	result, err := r.db.Exec("DELETE FROM `proveedores` WHERE `id_proveedor` = ?", id)
	if err != nil {
		return false, err
	}
	rowsAffected, err := result.RowsAffected()
	return rowsAffected > 0, err
}

// ==========================================
// 3. APPLICATION: Casos de Uso (Funciones independientes)
// ==========================================

func HandleGetProveedor(w http.ResponseWriter, r *http.Request, repo *ProveedorRepository, idStr string) {
	if idStr != "" {
		id, err := strconv.Atoi(idStr)
		if err != nil {
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{"error": "ID de proveedor inválido"})
			return
		}

		p, err := repo.GetByID(id)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
			return
		}
		if p == nil {
			json.NewEncoder(w).Encode(map[string]string{"message": "Registro no encontrado"})
			return
		}
		json.NewEncoder(w).Encode(p)
	} else {
		proveedores, err := repo.GetAll()
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
			return
		}
		json.NewEncoder(w).Encode(proveedores)
	}
}

func HandleCreateProveedor(w http.ResponseWriter, r *http.Request, repo *ProveedorRepository) {
	var p Proveedor
	if err := json.NewDecoder(r.Body).Decode(&p); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "JSON inválido"})
		return
	}

	insertID, err := repo.Create(p)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success":      true,
		"id_proveedor": insertID,
	})
}

func HandleUpdateProveedor(w http.ResponseWriter, r *http.Request, repo *ProveedorRepository, idStr string) {
	if idStr == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "Se requiere el campo id_proveedor"})
		return
	}

	id, err := strconv.Atoi(idStr)
	if err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "ID de proveedor inválido"})
		return
	}

	var p Proveedor
	if err := json.NewDecoder(r.Body).Decode(&p); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "JSON inválido"})
		return
	}

	updated, err := repo.Update(id, p)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"updated": updated,
	})
}

func HandleDeleteProveedor(w http.ResponseWriter, r *http.Request, repo *ProveedorRepository, idStr string) {
	if idStr == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "Se requiere el campo id_proveedor"})
		return
	}

	id, err := strconv.Atoi(idStr)
	if err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "ID de proveedor inválido"})
		return
	}

	deleted, err := repo.Delete(id)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"deleted": deleted,
	})
}

// ==========================================
// 4. PRESENTATION: Controladores y Middlewares
// ==========================================
var apiToken string

func EnableCORS(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")

		if r.Method == http.MethodOptions {
			w.WriteHeader(http.StatusOK)
			return
		}
		next(w, r)
	}
}

func Authenticate(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		authHeader := r.Header.Get("Authorization")
		parts := strings.SplitN(authHeader, " ", 2)
		if len(parts) != 2 || parts[0] != "Bearer" || parts[1] != apiToken {
			w.WriteHeader(http.StatusUnauthorized)
			json.NewEncoder(w).Encode(map[string]interface{}{
				"success": false,
				"error":   "Acceso no autorizado.",
			})
			return
		}
		next(w, r)
	}
}

func ProveedorHandler(repo *ProveedorRepository) http.HandlerFunc {
	return EnableCORS(Authenticate(func(w http.ResponseWriter, r *http.Request) {
		idStr := r.URL.Query().Get("id_proveedor")

		switch r.Method {
		case http.MethodGet:
			HandleGetProveedor(w, r, repo, idStr)
		case http.MethodPost:
			HandleCreateProveedor(w, r, repo)
		case http.MethodPut:
			HandleUpdateProveedor(w, r, repo, idStr)
		case http.MethodDelete:
			HandleDeleteProveedor(w, r, repo, idStr)
		default:
			w.WriteHeader(http.StatusMethodNotAllowed)
		}
	}))
}

// ==========================================
// 5. MAIN: Inicialización del Servidor
// ==========================================
func main() {
	// Carga el archivo .env local si está presente de forma aislada
	_ = godotenv.Load()

	// Validación estricta: detiene la ejecución si el token no está configurado
	apiToken = os.Getenv("API_TOKEN_PROVEEDORES")
	if apiToken == "" {
		log.Fatal("Error: La variable de entorno API_TOKEN_PROVEEDORES no está configurada.")
	}

	dsn := "root:@tcp(localhost:3306)/empresa?parseTime=true&charset=utf8mb4"
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		log.Fatalf("Error al conectar a la BD: %v", err)
	}
	defer db.Close()

	if err := db.Ping(); err != nil {
		log.Fatalf("No se pudo conectar con la BD: %v", err)
	}

	repo := NewProveedorRepository(db)

	mux := http.NewServeMux()
	mux.HandleFunc("/proveedores/", ProveedorHandler(repo))

	log.Println("Servidor modular con Go puro corriendo en el puerto 8080...")
	if err := http.ListenAndServe(":8080", mux); err != nil {
		log.Fatalf("Error al iniciar el servidor: %v", err)
	}
}
