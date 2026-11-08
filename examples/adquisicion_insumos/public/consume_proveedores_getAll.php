<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/examples/adquisicion_insumos/public/api/ProveedoresApi.php';
$token = 'token_fcef29077f44ad4584317ef1af4d1998';
$primaryKey = 'id_proveedor';

$api = new ApiClient($baseUrl, $token, $primaryKey);

$response = $api->getAll();
echo json_encode(['success' => true, 'data' => $response]);

exit;
