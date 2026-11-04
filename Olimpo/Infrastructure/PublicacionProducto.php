<?php
namespace Olimpo\Infrastructure;

use Olimpo\Domain\PublicacionProductoInterface;

class PublicacionProducto implements PublicacionProductoInterface {
    public function publicar(int $productoId): bool {
        return true;
    }
}
