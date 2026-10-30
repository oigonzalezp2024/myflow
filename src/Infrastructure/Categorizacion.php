<?php
namespace App\Infrastructure;

use App\Domain\CategorizacionInterface;

class Categorizacion implements CategorizacionInterface {
    public function categorizarProducto(int $productoId, int $categoriaId, int $usuarioSuperiorId): bool {
        return true;
    }
}
