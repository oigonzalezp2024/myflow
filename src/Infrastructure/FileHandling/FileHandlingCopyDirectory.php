<?php

declare(strict_types=1);

namespace App\Infrastructure\FileHandling;

use App\Infrastructure\FileHandling\CopyDirectoryRequest;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class FileHandlingCopyDirectory
{
    private const ALLOWED_BASE_PATH = '/var/www/html/storage/'; // Ajusta según tu entorno o desactiva si usas rutas absolutas controladas

    public function __invoke(CopyDirectoryRequest $request): true
    {
        $source = $this->sanitizeAndValidatePath($request->sourceDirectory, true);
        $destination = $this->sanitizeAndValidatePath($request->destinationDirectory, false);

        // Prevenir copiar un directorio dentro de sí mismo
        if (str_starts_with($destination, $source . DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException("No se puede copiar un directorio dentro de su propia ruta descendiente.");
        }

        $this->ensureDestinationExists($destination, $request->permissions);

        $iterator = $this->createIterator($source);

        foreach ($iterator as $item) {
            $subPath = $iterator->getSubPathName();
            $destPath = $destination . DIRECTORY_SEPARATOR . $subPath;

            if ($item->isDir()) {
                $this->ensureDestinationExists($destPath, $request->permissions);
            } else {
                $this->copyFileSafely($item, $destPath);
            }
        }

        return true;
    }

    /**
     * Sanitiza la ruta y previene ataques de Path Traversal (Directory Traversal).
     */
    private function sanitizeAndValidatePath(string $path, bool $mustExist): string
    {
        // Resolver ruta real o normalizarla de forma segura
        $realPath = realpath($path);

        if ($mustExist) {
            if ($realPath === false || !is_dir($realPath)) {
                throw new InvalidArgumentException("La ruta de origen no es válida o no existe.");
            }
            if (!is_readable($realPath)) {
                throw new RuntimeException("El recurso de origen no cuenta con permisos de lectura.");
            }
            $sanitized = $realPath;
        } else {
            // Para el destino, validamos el directorio padre si aún no existe
            $dirName = dirname($path);
            $realParent = realpath($dirName);
            
            if ($realParent === false || !is_writable($realParent)) {
                throw new RuntimeException("El directorio padre de destino no es válido o no tiene permisos de escritura.");
            }
            $sanitized = rtrim($realParent, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($path);
        }

        /* 
         * Opcional pero recomendado: Restringir a una ruta base segura (Sandbox Check)
         * if (!str_starts_with($sanitized, self::ALLOWED_BASE_PATH)) {
         *     throw new InvalidArgumentException("Acceso denegado: La ruta está fuera del directorio permitido.");
         * }
         */

        return $sanitized;
    }

    private function ensureDestinationExists(string $path, int $permissions): void
    {
        if (!is_dir($path)) {
            // Se usa umask o control estricto de permisos para evitar herencia insegura
            $oldUmask = umask(0);
            try {
                if (!mkdir($path, $permissions, true) && !is_dir($path)) {
                    throw new RuntimeException("Error crítico al aprovisionar el directorio de destino.");
                }
            } finally {
                umask($oldUmask);
            }
        }
    }

    private function createIterator(string $source): \RecursiveIteratorIterator
    {
        try {
            return new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $source,
                    \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS
                ),
                \RecursiveIteratorIterator::SELF_FIRST
            );
        } catch (UnexpectedValueException $e) {
            // No exponer la traza ni detalles sensibles del servidor al exterior
            throw new RuntimeException("Error interno al procesar la estructura de directorios.");
        }
    }

    private function copyFileSafely(\SplFileInfo $item, string $destPath): void
    {
        $realPath = $item->getRealPath();

        if ($realPath === false || !is_readable($realPath)) {
            throw new RuntimeException("Error al validar la integridad de uno de los archivos fuente.");
        }

        // Evitar sobreescrituras accidentales de archivos críticos del sistema si fuera necesario,
        // o asegurar que la copia mantenga restricciones de permisos seguras.
        if (!@copy($realPath, $destPath)) {
            // Registrar el error real en los logs del servidor (error_log), 
            // pero lanzar un mensaje genérico al cliente para evitar Information Disclosure.
            error_log("Fallo al copiar archivo de [{$realPath}] a [{$destPath}]");
            throw new RuntimeException("No se pudo completar la transferencia de archivos.");
        }

        // Asegurar que el archivo copiado tenga permisos restrictivos seguros (ej. 0644)
        @chmod($destPath, 0644);
    }
}
