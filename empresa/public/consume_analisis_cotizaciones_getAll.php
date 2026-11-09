<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/ApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/empresa/public/api/AnalisisCotizacionesAPI.php';
$token = 'token_1a8e975cd70ffbeeba8876fbb0e050de';
$primaryKey = 'id_analisis';

$api = new ApiClient($baseUrl, $token, $primaryKey);

$response = $api->getAll();
echo json_encode(['success' => true, 'data' => $response]);

exit;
