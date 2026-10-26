<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../client/users/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/public/api/users/index.php';
$token = 'token_3075b5bded622b2113aba9daf51d90f0';
$primaryKey = 'id_user';

$api = new ApiClient($baseUrl, $token, $primaryKey);

// 2. Capturar datos entrantes (Soporta JSON o FormData / x-www-form-urlencoded)
$inputData = json_decode(file_get_contents('php://input'), true);
$requestData = !empty($inputData) ? $inputData : $_POST;

if (empty($requestData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en la solicitud.']);
    exit();
}

// 3. Mapeo y filtrado de columnas permitidas en la tabla `users`
$allowedColumns = array (
  0 => 'id_user',
  1 => 'user_name',
  2 => 'user_password',
);
$payload = [];

foreach ($allowedColumns as $col) {
    if ($col === $primaryKey) continue; // La PK generalmente se autoincrementa
    if (isset($requestData[$col])) {
        $payload[$col] = $requestData[$col];
    }
}

$id = $requestData[$primaryKey] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falta el identificador primario para actualizar.']);
    exit();
}
try {
    $response = $api->update($id, $payload);
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $response]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
