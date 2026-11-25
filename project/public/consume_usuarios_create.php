<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/project/public/api/UsuariosApi.php';
$token = 'token_cf92c30c9f22db12cb052c52193edd3f';
$primaryKey = 'id_usuario';

$api = new ApiClient($baseUrl, $token, $primaryKey);

// 2. Capturar datos entrantes (Soporta JSON o FormData / x-www-form-urlencoded)
$inputData = json_decode(file_get_contents('php://input'), true);
$requestData = !empty($inputData) ? $inputData : $_POST;

if (empty($requestData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en la solicitud.']);
    exit();
}

// 3. Mapeo y filtrado de columnas permitidas en la tabla `usuarios`
$allowedColumns = array (
  0 => 'id_usuario',
  1 => 'nombre',
  2 => 'pass',
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
