<?php
namespace App\Infrastructure;

use App\Domain\RegistroProductoInterface;

class RegistroProducto implements RegistroProductoInterface {
    public function registrar(int $compraId, array $datosProducto): int {
        return 500; // ID simulado del producto registrado
    }
}
