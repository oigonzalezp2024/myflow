<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$target_dir = "uploads/";
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

$filesList = [];

if (is_dir($target_dir)) {
    $files = scandir($target_dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $filePath = $target_dir . $file;
        
        if (is_file($filePath)) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (in_array($extension, $allowed_extensions, true)) {
                $filesList[] = [
                    'file_name' => $file,
                    'path'      => $filePath,
                    'size'      => filesize($filePath),
                    'mtime'     => filemtime($filePath),
                    'type'      => $extension
                ];
            }
        }
    }

    // Ordena descendentemente por mtime (más reciente primero)
    usort($filesList, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'images'  => $filesList
]);
