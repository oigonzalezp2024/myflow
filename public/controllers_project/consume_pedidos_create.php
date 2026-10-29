<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/public/api/insumos/pedidos/';
$token = 'token_fd2cd01afc151faa68c72baf82e6da16';
$primaryKey = 'id_pedido';

$api = new ApiClient($baseUrl, $token, $primaryKey);

// 2. Capturar datos entrantes (Soporta JSON o FormData / x-www-form-urlencoded)
$inputData = json_decode(file_get_contents('php://input'), true);
$requestData = !empty($inputData) ? $inputData : $_POST;

if (empty($requestData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en la solicitud.']);
    exit();
}

// 3. Mapeo y filtrado de columnas permitidas en la tabla `pedidos`
$allowedColumns = array (
  0 => 'id_pedido',
  1 => 'proveedor_id',
  2 => 'fecha',
);
$payload = [];

foreach ($allowedColumns as $col) {
    if ($col === $primaryKey) continue; // La PK generalmente se autoincrementa
    if (isset($requestData[$col])) {
        $payload[$col] = $requestData[$col];
    }
}

// 4. Ejecución de creación hacia el API final
try {
    $response = $api->create($payload);
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $response]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;
