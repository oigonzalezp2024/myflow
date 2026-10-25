<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../client/users/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/public/api/users/index.php';
// $baseUrl = 'http://localhost/myflow/public/controllers/consume_users_getById.php';
$token = 'token_3075b5bded622b2113aba9daf51d90f0';
$primaryKey = 'id_user';

$api = new ApiClient($baseUrl, $token, $primaryKey);

$id = $_GET['id_user'] ?? null;

try {
    $response = $api->getById($id);
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $response]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
