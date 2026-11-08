<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/examples/adquisicion_insumos/public/api/CotizacionesApi.php';
$token = 'token_3caf618c813c5d813d56fcf4f949f0b1';
$primaryKey = 'id_cotizacion';

$api = new ApiClient($baseUrl, $token, $primaryKey);

$response = $api->getAll();
echo json_encode(['success' => true, 'data' => $response]);

exit;
