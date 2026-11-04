<?php
namespace Olimpo\Infrastructure;

use Olimpo\Domain\RegistroProductoInterface;

class RegistroProducto implements RegistroProductoInterface {
    public function registrar(int $compraId, array $datosProducto): int {
        return 500; // ID simulado del producto registrado
    }
}
