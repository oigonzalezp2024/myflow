<?php
namespace Olimpo\Infrastructure;

use Olimpo\Domain\CategorizacionInterface;

class Categorizacion implements CategorizacionInterface {
    public function categorizarProducto(int $productoId, int $categoriaId, int $usuarioSuperiorId): bool {
        return true;
    }
}
