<?php

declare(strict_types=1);

namespace App\Infrastructure\FileHandling;

use InvalidArgumentException;
use RuntimeException;

class WeaviateEnvironmentGenerator
{
    private string $templatePath;

    public function __construct(string $templatePath)
    {
        if (!file_exists($templatePath) || !is_readable($templatePath)) {
            throw new RuntimeException("El archivo de plantilla no existe o no se puede leer: {$templatePath}");
        }

        $this->templatePath = $templatePath;
    }

    /**
     * Genera un archivo docker-compose.yml independiente a partir de la plantilla.
     *
     * @param string $outputDirectory Ruta de la carpeta donde se guardará el nuevo entorno.
     * @param string $environmentName Nombre único del entorno (ej. 'cliente_a', 'rag_inventario').
     * @param int    $httpPort        Puerto HTTP único en el host (reemplaza 9035).
     * @param int    $grpcPort        Puerto gRPC único en el host (reemplaza 50051).
     * @param bool   $isolateVolumes  Si es true, agrega un volumen local para evitar colisiones de datos.
     *
     * @return string Ruta completa del archivo generado.
     */
    public function generate(
        string $outputDirectory,
        string $environmentName,
        int $httpPort,
        int $grpcPort,
        bool $isolateVolumes = true
    ): string {
        $content = file_get_contents($this->templatePath);

        if ($content === false) {
            throw new RuntimeException("Error al leer el contenido de la plantilla.");
        }

        // Normalizar nombre del entorno para evitar caracteres inválidos en Docker/YAML
        $safeEnvName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(trim($environmentName)));
        if (empty($safeEnvName)) {
            throw new InvalidArgumentException("El nombre del entorno no es válido.");
        }

        // 1. Reemplazar el argumento del puerto interno/ejecución ('9035')
        $content = str_replace(
            "- '9035'",
            "- '{$httpPort}'",
            $content
        );

        // 2. Reemplazar el mapeo de puertos expuestos en el host (9035:9035 y 50051:50051)
        $content = str_replace(
            "- 9035:9035",
            "- {$httpPort}:{$httpPort}",
            $content
        );

        $content = str_replace(
            "- 50051:50051",
            "- {$grpcPort}:50051",
            $content
        );

        // 3. Reemplazar CLUSTER_HOSTNAME ('node1')
        $content = str_replace(
            "CLUSTER_HOSTNAME: 'node1'",
            "CLUSTER_HOSTNAME: '{$safeEnvName}'",
            $content
        );

        // 4. Aislar el nombre del servicio para evitar conflictos en redes compartidas de Docker
        $content = preg_replace(
            '/^(\s*)weaviate:/m',
            "$1weaviate_{$safeEnvName}:",
            $content,
            1
        );

        // 5. Inyectar volumen independiente para persistencia de datos por entorno
        if ($isolateVolumes) {
            $volumeBlock = "    volumes:\n      - ./weaviate_data_{$safeEnvName}:/var/lib/weaviate\n    restart:";
            $content = str_replace("    restart:", $volumeBlock, $content);
        }

        // Crear el directorio de destino de forma recursiva si no existe
        if (!is_dir($outputDirectory)) {
            if (!mkdir($outputDirectory, 0755, true) && !is_dir($outputDirectory)) {
                throw new RuntimeException("No se pudo crear el directorio de destino: {$outputDirectory}");
            }
        }

        $outputFile = rtrim($outputDirectory, '/\\') . DIRECTORY_SEPARATOR . 'docker-compose.yml';

        if (file_put_contents($outputFile, $content) === false) {
            throw new RuntimeException("No se pudo escribir el archivo en: {$outputFile}");
        }

        return $outputFile;
    }
}
