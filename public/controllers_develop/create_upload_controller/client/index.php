<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$folderName = trim($_POST['destination_folder'] ?? '');

if (empty($folderName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "El campo 'destination_folder' es obligatorio."], JSON_UNESCAPED_UNICODE);
    exit;
}

$putEndpointUrl = 'http://localhost/myflow/public/controllers_develop/create_upload_controller/';

$ch = curl_init($putEndpointUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'destination_folder' => $folderName
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpCode);
echo $response;
