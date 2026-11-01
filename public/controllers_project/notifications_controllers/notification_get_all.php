<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clients/NotificationApiClient.php';

// 1. Configuración de conexión y credenciales
$baseUrl = 'http://localhost/myflow/public/api/insumos/notificaciones/';
$token = 'token_8059bb5a7a5418bed7cfbd71b9f36f8e';
$primaryKey = 'id_notificacion';

$api = new ApiClient($baseUrl, $token, $primaryKey);

$response = $api->getAll();
echo json_encode(['success' => true, 'data' => $response]);

exit;
