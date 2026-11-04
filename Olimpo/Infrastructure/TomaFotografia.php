<?php
namespace Olimpo\Infrastructure;

use Olimpo\Domain\TomaFotografiaInterface;

class TomaFotografia implements TomaFotografiaInterface {
    public function capturarYAsociarFoto(int $productoId, string $rutaImagen): bool {
        return true;
    }
}
