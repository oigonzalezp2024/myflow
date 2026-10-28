<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/public/api/usuarios/UsuariosApi.php';
$token = 'token_db2f71a88ef4d12f921c5c58849e828d';
$primaryKey = 'id_usuario';

$api = new ApiClient($baseUrl, $token, $primaryKey);

// 2. Leer los datos enviados dinámicamente en formato JSON desde el frontend (fetch)
$inputJson = file_get_contents('php://input');
$data = json_decode($inputJson, true);

// Validar que se hayan recibido datos
if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'No se recibieron datos válidos o el formato JSON es incorrecto.'
    ]);
    exit;
}

try {
    // 3. Ejecutar la creación a través del cliente API
    $response = $api->create($data);
    
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Datos enviados y procesados correctamente.',
        'data' => $response
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
