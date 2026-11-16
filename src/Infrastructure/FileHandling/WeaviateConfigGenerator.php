<?php

namespace App\Infrastructure\FileHandling;

class WeaviateConfigGenerator
{
    /**
     * Genera el archivo de conexión weaviate.go con el puerto HTTP dinámico.
     *
     * @param string $templatePath Ruta al archivo plantilla weaviate.go.
     * @param string $outputPath Ruta donde se guardará el archivo weaviate.go generado.
     * @param int $httpPort Puerto HTTP asignado dinámicamente.
     * @return bool Retorna verdadero si la operación fue exitosa.
     * @throws \Exception Si la plantilla no existe o no se puede escribir el archivo.
     */
    public function generate(string $templatePath, string $outputPath, int $httpPort): bool
    {
        if (!file_exists($templatePath)) {
            throw new \Exception("El archivo de plantilla '{$templatePath}' no existe.");
        }

        $templateContent = file_get_contents($templatePath);

        // Reemplaza el puerto por defecto (ej. 9035 o 9020) por el puerto HTTP asignado
        $newContent = preg_replace('/\b(9035|9020|9036)\b/', (string)$httpPort, $templateContent);

        $directory = dirname($outputPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_put_contents($outputPath, $newContent) === false) {
            throw new \Exception("No se pudo escribir el archivo en '{$outputPath}'.");
        }

        return true;
    }
}
