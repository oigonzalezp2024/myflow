<?php

namespace App\Infrastructure\FileHandling;

class RagServerMainGenerator
{
    /**
     * Toma el archivo plantilla main.go, reemplaza la variable de entorno SERVERPORT
     * por <NODO>_SERVERPORT en mayúsculas y asigna el puerto único correspondiente.
     *
     * @param string $templatePath Ruta del archivo plantilla main.go.
     * @param string $outputPath Ruta destino donde se guardará el main.go generado.
     * @param string $nodeName Nombre del nodo (ej. 'oooo', 'uuuu', 'aaa').
     * @param int $serverPort Puerto HTTP asignado al servidor Go (ej. 9036, 9037).
     * @return bool
     * @throws \Exception
     */
    public function generate(string $templatePath, string $outputPath, string $nodeName, int $serverPort): bool
    {
        if (!file_exists($templatePath)) {
            throw new \Exception("El archivo plantilla main.go no existe en: {$templatePath}");
        }

        $templateContent = file_get_contents($templatePath);
        if ($templateContent === false) {
            throw new \Exception("No se pudo leer el archivo plantilla: {$templatePath}");
        }

        // Sanitiza el nombre del nodo para formar un identificador válido de variable de entorno
        $sanitizedNode = preg_replace('/[^a-zA-Z0-9_]/', '_', trim($nodeName));
        $envVarName = strtoupper($sanitizedNode) . '_SERVERPORT';

        // Reemplaza tanto el nombre de la variable de entorno como el puerto por defecto
        $search = 'os.Getenv("SERVERPORT"), "9020"';
        $replace = sprintf('os.Getenv("%s"), "%d"', $envVarName, $serverPort);

        $updatedContent = str_replace($search, $replace, $templateContent);

        $targetDir = dirname($outputPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (file_put_contents($outputPath, $updatedContent) === false) {
            throw new \Exception("No se pudo escribir el archivo generado en: {$outputPath}");
        }

        return true;
    }
}
