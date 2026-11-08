<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/examples/adquisicion_insumos/public/api/CotizacionesApi.php';
$token = 'token_3caf618c813c5d813d56fcf4f949f0b1';
$primaryKey = 'id_cotizacion';

$api = new ApiClient($baseUrl, $token, $primaryKey);

// 2. Capturar datos entrantes (Soporta JSON o FormData / x-www-form-urlencoded)
$inputData = json_decode(file_get_contents('php://input'), true);
$requestData = !empty($inputData) ? $inputData : $_GET;

if (empty($requestData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en la solicitud.']);
    exit();
}

// 3. Mapeo y filtrado de columnas permitidas en la tabla `cotizaciones`
$allowedColumns = array (
  0 => 'id_cotizacion',
  1 => 'producto',
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
