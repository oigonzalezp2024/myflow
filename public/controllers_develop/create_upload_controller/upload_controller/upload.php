<?php
header('Content-Type: application/json; charset=utf-8');

function jsonResponse(bool $success, string $message, array $extra = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

$target_dir = "uploads/";

// 1. Verificar si se envió la petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES["fileToUpload"]) || $_FILES["fileToUpload"]["error"] !== UPLOAD_ERR_OK) {
        jsonResponse(false, "Error en la recepción del archivo.", [], 400);
    }

    // 2. Validar tamaño del archivo (Máximo 500 KB)
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        jsonResponse(false, "El archivo supera el límite permitido de 500 KB.", [
            'size' => $_FILES["fileToUpload"]["size"]
        ], 400);
    }

    // 3. Validar el tipo MIME real del archivo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($_FILES["fileToUpload"]["tmp_name"]);
    
    $allowedMimeTypes = [
        "image/jpeg"      => "jpg",
        "image/png"       => "png",
        "image/gif"       => "gif",
        "application/pdf" => "pdf" // <-- Añadido soporte MIME para PDF
    ];

    if (!array_key_exists($mimeType, $allowedMimeTypes)) {
        jsonResponse(false, "Tipo MIME no permitido ({$mimeType}). Solo se admiten JPG, PNG, GIF y PDF.", [
            'mime' => $mimeType
        ], 415);
    }

    $extension = $allowedMimeTypes[$mimeType];

    // 4. Generar un nombre único y seguro
    $newFileName = bin2hex(random_bytes(16)) . "." . $extension;
    $target_file = $target_dir . $newFileName;

    // 5. Mover el archivo
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        jsonResponse(true, "Archivo ingerido e inspeccionado exitosamente.", [
            'file_name' => $newFileName,
            'mime_type' => $mimeType,
            'size'      => $_FILES["fileToUpload"]["size"],
            'path'      => $target_file
        ], 200);
    } else {
        jsonResponse(false, "Error interno al guardar el archivo en el servidor.", [], 500);
    }
} else {
    jsonResponse(false, "Método no permitido.", [], 405);
}
