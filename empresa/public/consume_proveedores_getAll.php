<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/empresa/public/api/ProveedoresAPI.php';
$token = 'token_891c160061bf493dca0c61437cd4861c';
$primaryKey = 'id_proveedor';

$api = new ApiClient($baseUrl, $token, $primaryKey);

$response = $api->getAll();
echo json_encode(['success' => true, 'data' => $response]);

exit;
