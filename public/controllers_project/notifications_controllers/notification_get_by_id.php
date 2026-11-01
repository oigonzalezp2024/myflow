<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/NotificationApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/public/api/insumos/notificaciones/';
$token = 'token_8059bb5a7a5418bed7cfbd71b9f36f8e';
$primaryKey = 'id_notificacion';

$api = new ApiClient($baseUrl, $token, $primaryKey);

// 2. Capturar datos entrantes (Soporta JSON o FormData / x-www-form-urlencoded)
$inputData = json_decode(file_get_contents('php://input'), true);
$requestData = !empty($inputData) ? $inputData : $_GET;

if (empty($requestData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en la solicitud.']);
    exit();
}

// 3. Mapeo y filtrado de columnas permitidas en la tabla `notificaciones`
$allowedColumns = array (
  0 => 'id_notificacion',
  1 => 'notificacion',
  2 => 'estado',
);
$payload = [];

foreach ($allowedColumns as $col) {
    if (isset($requestData[$col])) {
        $payload[$col] = $requestData[$col];
    }
}

$response = $api->getById($payload[$primaryKey]);
echo json_encode(['success' => true, 'data' => $response]);
exit;
