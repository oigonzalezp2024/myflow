<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Bogota');

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
use App\Infrastructure\FileHandling\WeaviateEnvironmentGenerator;

/**
 * Obtiene todos los puertos actualmente asignados en Docker (activos o detenidos)
 * junto con los registrados históricamente.
 */
function getUnavailablePorts(array $registryData): array
{
    $usedPorts = [];

    foreach ($registryData as $entry) {
        if (isset($entry['http_port'])) {
            $usedPorts[] = (int)$entry['http_port'];
        }
        if (isset($entry['grpc_port'])) {
            $usedPorts[] = (int)$entry['grpc_port'];
        }
    }

    $dockerOutput = [];
    @exec('docker ps -a --format "{{.Ports}}" 2>&1', $dockerOutput);
    foreach ($dockerOutput as $line) {
        if (preg_match_all('/(?:0\.0\.0\.0|127\.0\.0\.1|\[::\]):(\d+)->/', $line, $matches)) {
            foreach ($matches[1] as $port) {
                $usedPorts[] = (int)$port;
            }
        }
    }

    return array_values(array_unique($usedPorts));
}

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

    $source = __DIR__ . '/ragserver/ragserver';
    $baseDestinationDir = __DIR__ . '/../../../empresa/ragservers/';

    if (!is_dir($baseDestinationDir)) {
        if (!mkdir($baseDestinationDir, 0755, true) && !is_dir($baseDestinationDir)) {
            throw new RuntimeException('No se pudo crear el directorio base de destino (ragservers).');
        }
    }

    $destination = $baseDestinationDir . $safeFolderName;
    $isUpdate = is_dir($destination);

    $request = new CopyDirectoryRequest(
        sourceDirectory: $source,
        destinationDirectory: $destination,
        permissions: 0755
    );
    
    // Carpeta de prueba específica y aislada para este proyecto
    $projectTestDir = $destination . '/test_' . $safeFolderName;
    if (!is_dir($projectTestDir)) {
        mkdir($projectTestDir, 0755, true);
    }

    $useCase = new FileHandlingCopyDirectory();
    $useCase($request);
    
    // Copiar el docker-compose.yml base a la carpeta de prueba exclusiva del proyecto
    $dockerComposeSource = __DIR__ . '/ragserver/tests/docker-compose.yml';
    $targetDockerCompose = $projectTestDir . "/docker-compose.yml";
    $useCase->copyFile($dockerComposeSource, $targetDockerCompose);

    // Registro JSON histórico en el mismo scope del script ejecutor (__DIR__)
    $registryFile = __DIR__ . '/registry.json';
    $registryData = [];
    if (file_exists($registryFile)) {
        $content = file_get_contents($registryFile);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $registryData = $decoded;
        }
    }

    $unavailablePorts = getUnavailablePorts($registryData);

    // Asignar puertos únicos sin colisión con registry.json ni contenedores existentes
    if (isset($registryData[$safeFolderName])) {
        $httpPort = (int)$registryData[$safeFolderName]['http_port'];
        $grpcPort = (int)$registryData[$safeFolderName]['grpc_port'];
    } else {
        $httpPort = 9036;
        while (in_array($httpPort, $unavailablePorts, true)) {
            $httpPort++;
        }
        $unavailablePorts[] = $httpPort;

        $grpcPort = 50052;
        while (in_array($grpcPort, $unavailablePorts, true)) {
            $grpcPort++;
        }
        $unavailablePorts[] = $grpcPort;
    }

    //// Generación del entorno Weaviate único con el nombre del proyecto
    $generatedEnvDir = $destination . '/ragservers/' . $safeFolderName;
    try {
        $generator = new WeaviateEnvironmentGenerator($targetDockerCompose);
        $generator->generate(
            outputDirectory: $generatedEnvDir,
            environmentName: $safeFolderName,
            httpPort: $httpPort,
            grpcPort: $grpcPort
        );
    } catch (Throwable $e) {
        error_log("Aviso WeaviateGenerator: " . $e->getMessage());
        throw new RuntimeException("Fallo al generar el archivo YAML del entorno Weaviate: " . $e->getMessage());
    }

    // Reemplazar explícitamente los puertos base (9036 y 50052) en el docker-compose.yml generado
    $generatedComposeFile = $generatedEnvDir . '/docker-compose.yml';
    if (file_exists($generatedComposeFile)) {
        $yamlContent = file_get_contents($generatedComposeFile);
        // Reemplaza tanto mapeos "9036:8080" como "9036:9036" o variables de entorno con 9036/50052
        $yamlContent = preg_replace('/\b9036\b/', (string)$httpPort, $yamlContent);
        $yamlContent = preg_replace('/\b50052\b/', (string)$grpcPort, $yamlContent);
        file_put_contents($generatedComposeFile, $yamlContent);
    }

    // Ejecución inmediata del contenedor usando el YAML específico generado para el proyecto
    $composeToRun = file_exists($generatedComposeFile) ? $generatedComposeFile : $targetDockerCompose;
    $workingDir = dirname($composeToRun);
    $projectSlug = strtolower($safeFolderName);

    if (is_dir($workingDir) && file_exists($composeToRun)) {
        $cdPrefix = PHP_OS_FAMILY === 'Windows' ? 'cd /d' : 'cd';
        $command = sprintf(
            '%s %s && docker compose -p %s -f %s up -d 2>&1',
            $cdPrefix,
            escapeshellarg($workingDir),
            escapeshellarg($projectSlug),
            escapeshellarg(basename($composeToRun))
        );
        
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        
        if ($exitCode !== 0) {
            // Limpiar el contenedor/red fallida si Docker llegó a crearlos antes del error de puerto
            @exec(sprintf(
                '%s %s && docker compose -p %s -f %s down 2>&1',
                $cdPrefix,
                escapeshellarg($workingDir),
                escapeshellarg($projectSlug),
                escapeshellarg(basename($composeToRun))
            ));

            $dockerError = implode(" | ", $output);
            error_log("Fallo al ejecutar Docker Compose en [{$workingDir}]. Salida: " . $dockerError);
            throw new RuntimeException("Error Docker [Exit {$exitCode}]: " . $dockerError);
        }
    }

    // Registrar en __DIR__ . '/registry.json' solo después de que Docker levantó exitosamente
    $registryData[$safeFolderName] = [
        'folder'     => $safeFolderName,
        'http_port'  => $httpPort,
        'grpc_port'  => $grpcPort,
        'created_at' => $registryData[$safeFolderName]['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($registryFile, json_encode($registryData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    ///////////////////////////////////////
    $sourceClients = __DIR__ . '/ragserver/company/clients/';
    $baseClientsDir = __DIR__ . '/../../../empresa/clients/ragserver/';

    if (!is_dir($baseClientsDir)) {
        mkdir($baseClientsDir, 0755, true);
    }

    $destinationClients = $baseClientsDir . $safeFolderName;
    $requestClients = new CopyDirectoryRequest(
        sourceDirectory: $sourceClients,
        destinationDirectory: $destinationClients,
        permissions: 0755
    );
    $useCase($requestClients);
    //////////////////

    ///////////////////////////////////////
    $sourcePublic = __DIR__ . '/ragserver/company/public/';
    $basePublicDir = __DIR__ . '/../../../empresa/public/';

    if (!is_dir($basePublicDir)) {
        mkdir($basePublicDir, 0755, true);
    }

    $destinationPublic = $basePublicDir . $safeFolderName;
    $requestPublic = new CopyDirectoryRequest(
        sourceDirectory: $sourcePublic,
        destinationDirectory: $destinationPublic,
        permissions: 0755
    );
    $useCase($requestPublic);
    //////////////////

    $copiedFolderIndexUrl = __DIR__ . "/../../controllers_project/" . rawurlencode($safeFolderName) . "/index.php";

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $isUpdate ? 'Directorio actualizado con éxito.' : 'Directorio creado con éxito.',
        'data' => [
            'folder_name' => $safeFolderName,
            'index_url'   => $copiedFolderIndexUrl,
            'ports'       => ['http' => $httpPort, 'grpc' => $grpcPort]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (InvalidArgumentException | RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => ['code' => 400, 'message' => $e->getMessage()]
    ], JSON_UNESCAPED_UNICODE);
}
