<?php
namespace App\Infrastructure;

use App\Domain\CompraMercanciaInterface;

class CompraMercancia implements CompraMercanciaInterface {
    public function ejecutarCompra(array $datosCompra): int {
        return 101; // ID simulado de la compra
    }
}
