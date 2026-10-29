<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales

$baseUrl = 'http://localhost/myflow/public/controllers/consume_proveedores_create.php';
$token = 'token_e146b7073904f432bfc121ea3e396708';
$primaryKey = 'id_proveedor';

$api = new ApiClient($baseUrl, $token, $primaryKey);

$data = [];
$data['proveedor_nombre'] = $input['proveedor_nombre'] ?? 'user_password99';
$data['vendedor'] = $input['vendedor'] ?? 'user_password99';

try {
    $response = $api->create($data);
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $response]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
