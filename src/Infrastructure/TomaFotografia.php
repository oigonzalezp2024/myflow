<?php
namespace App\Infrastructure;

use App\Domain\TomaFotografiaInterface;

class TomaFotografia implements TomaFotografiaInterface {
    public function capturarYAsociarFoto(int $productoId, string $rutaImagen): bool {
        return true;
    }
}
