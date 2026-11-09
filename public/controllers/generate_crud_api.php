<?php
/**
 * Orquestador principal de peticiones API
 * Separa la infraestructura HTTP de la lógica de negocio.
 */

// --- CONFIGURACIÓN DE CABECERAS Y CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- CAPTURA DE ENTRADA ---
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];
$action = $data['action'] ?? 'generate';

// --- CARGA DE SERVICIOS / HANDLERS ---
// (Asegúrate de ajustar las rutas según la estructura de tu proyecto)
require_once __DIR__ . '/handlers/SaveFileHandler.php';
require_once __DIR__ . '/handlers/GenerateClientHandler.php';
require_once __DIR__ . '/handlers/GenerateCrudHandler.php';

try {
    // --- ORQUESTACIÓN DE RESPONSABILIDADES ---
    switch ($action) {
        case 'save':
            SaveFileHandler::execute($data);
            break;

        case 'generate_client':
            GenerateClientHandler::execute($data);
            break;

        case 'generate':
        default:
            GenerateCrudHandler::execute($data);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno en el servidor: ' . $e->getMessage()
    ]);
}
