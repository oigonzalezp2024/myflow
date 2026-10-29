<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

require_once __DIR__ . '/../clients/ApiPedidoClient.php';

// 1. Configuración de conexión y credenciales

$baseUrl = 'http://localhost/myflow/public/api/insumos/pedidos/index.php';
$token = 'token_fd2cd01afc151faa68c72baf82e6da16';
$primaryKey = 'id_pedido';

$api = new ApiClient($baseUrl, $token, $primaryKey);

$data = [];
$data['proveedor_id'] = $input['proveedor_id'] ?? 1;
$data['fecha'] = $input['fecha'] ?? '2026-04-29';

try {
    $response = $api->create($data);
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $response]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
