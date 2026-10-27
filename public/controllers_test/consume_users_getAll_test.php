<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales

$baseUrl = 'http://localhost/myflow/public/controllers/consume_usuarios_getAll.php';
$token = 'token_72df870e596604dcec52604d0f127216';
$primaryKey = 'id_usuario';

$api = new ApiClient($baseUrl, $token, $primaryKey);

try {
    $response = $api->getAll();
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $response]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
