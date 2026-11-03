<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => ['code' => 405, 'message' => "Método no permitido. Utilice PUT."]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Infrastructure\FileHandling\CopyDirectoryRequest;
use App\Infrastructure\FileHandling\FileHandlingCopyDirectory;

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!is_array($data) || empty($data['destination_folder'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => ['code' => 400, 'message' => "Se requiere 'destination_folder'."]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $folderName = trim($data['destination_folder']);
    $safeFolderName = basename($folderName);

    $source = __DIR__ . '/upload_controller';
    $baseDestinationDir = __DIR__ . '/../../controllers_project/';
    $destination = $baseDestinationDir . $safeFolderName;

    $isUpdate = is_dir($destination);

    $request = new CopyDirectoryRequest(
        sourceDirectory: $source,
        destinationDirectory: $destination,
        permissions: 0755
    );

    $useCase = new FileHandlingCopyDirectory();
    $useCase($request);

    // Ruta web específica que apunta al index dentro de la carpeta recién COPIADA
    $copiedFolderIndexUrl = __DIR__ . "/../../controllers_project/" . rawurlencode($safeFolderName) . "/index.php";

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $isUpdate ? 'Directorio actualizado con éxito.' : 'Directorio creado con éxito.',
        'data' => [
            'folder_name' => $safeFolderName,
            'index_url'   => $copiedFolderIndexUrl
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (InvalidArgumentException | RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => ['code' => 400, 'message' => $e->getMessage()]
    ], JSON_UNESCAPED_UNICODE);
}
