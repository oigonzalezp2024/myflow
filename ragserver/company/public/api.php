<?php

declare(strict_types=1);

// Cabeceras obligatorias para aceptar JSON y evitar problemas de CORS si es necesario
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Si es una petición OPTIONS (preflight de CORS), la respondemos y terminamos
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Método no permitido. Solo se acepta POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Leer el cuerpo crudo de la petición (JSON enviado desde el JS)
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

// Extraer y validar el mensaje
$mensaje = isset($data['mensaje']) ? trim($data['mensaje']) : '';

if ($mensaje === '') {
    http_response_code(400);
    echo json_encode([
        'error' => 'El campo "mensaje" es obligatorio y no puede estar vacío.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Requerir tu cliente de RAG (ajusta la ruta según la ubicación de tu archivo)
    require_once __DIR__ . '/../Clients/RagApiClient.php';

    $ragClient = new RagApiClient('http://localhost:9020');

    // Consultar al servidor Go/RAG usando el texto dictado por voz del usuario
    $queryResult = $ragClient->query($mensaje);

    // Responder con éxito al frontend en formato JSON puro
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'mensaje_recibido' => $mensaje,
        'respuesta_rag' => $queryResult
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Falla en la operación del servidor RAG: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
