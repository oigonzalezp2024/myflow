<?php
class SaveFileHandler {
    public static function execute(array $data): void {
        $code = $data['code'] ?? '';
        $path = trim($data['filePath'] ?? '', '/\\');
        $fileName = trim($data['fileName'] ?? '');

        if (empty($code) || empty($fileName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El código y el nombre del archivo son obligatorios.']);
            exit();
        }

        if (!str_ends_with(strtolower($fileName), '.php') && !str_ends_with(strtolower($fileName), '.js')) {
            $fileName .= '.php';
        }

        $dirPath = __DIR__ . "/../../"; // Ajusta el nivel de directorio según tu estructura
        if (!empty($path)) {
            $dirPath .= DIRECTORY_SEPARATOR . $path;
        }

        if (!is_dir($dirPath)) {
            if (!mkdir($dirPath, 0755, true)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => "No se pudo crear el directorio: {$dirPath}"]);
                exit();
            }
        }

        $fullFilePath = $dirPath . DIRECTORY_SEPARATOR . $fileName;

        if (file_put_contents($fullFilePath, $code) !== false) {
            echo json_encode([
                'success' => true,
                'message' => "Archivo creado exitosamente en: {$fullFilePath}",
                'relativePath' => (!empty($path) ? $path . '/' : '') . $fileName
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error al escribir el archivo en {$fullFilePath}"]);
        }
        exit();
    }
}
