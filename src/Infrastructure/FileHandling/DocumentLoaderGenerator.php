<?php

declare(strict_types=1);

class DocumentLoaderGenerator
{
    /**
     * Genera un archivo a partir de una plantilla reemplazando la ruta del require y el puerto.
     *
     * @param string $templatePath Ruta al archivo de plantilla original.
     * @param string $destinationPath Ruta donde se guardará el nuevo archivo generado.
     * @param string $newRequirePath Nueva ruta relativa o absoluta para el require_once (línea 8).
     * @param int $newPort Nuevo puerto para la instancia de RagApiClient (línea 16).
     * @return bool True si se generó correctamente, false en caso contrario.
     * @throws Exception Si la plantilla no existe o no se puede escribir el archivo.
     */
    public static function generate(
        string $templatePath,
        string $destinationPath,
        string $newRequirePath,
        int $newPort
    ): bool {
        if (!file_exists($templatePath)) {
            throw new Exception("El archivo de plantilla no existe en: {$templatePath}");
        }

        $content = file_get_contents($templatePath);

        // 1. Reemplazar la línea 8 con la nueva ruta del require_once
        $content = preg_replace(
            "/require_once\s+.*?RagApiClient\.php['\"];/",
            "require_once '{$newRequirePath}';",
            $content
        );

        // 2. Reemplazar la línea 16 con el nuevo puerto para RagApiClient
        $content = preg_replace(
            "/new\s+RagApiClient\s*\(\s*['\"]http:\/\/localhost:\d+['\"]\s*\)\s*;/",
            "new RagApiClient('http://localhost:{$newPort}');",
            $content
        );

        // Asegurarse de que el directorio de destino exista
        $destinationDir = dirname($destinationPath);
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                throw new Exception("No se pudo crear el directorio de destino: {$destinationDir}");
            }
        }

        // Guardar el nuevo contenido en el archivo de destino
        return file_put_contents($destinationPath, $content) !== false;
    }
}
