<?php
namespace App\Infrastructure;

use App\Domain\PublicacionProductoInterface;

class PublicacionProducto implements PublicacionProductoInterface {
    public function publicar(int $productoId): bool {
        return true;
    }
}
