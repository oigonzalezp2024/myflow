<?php
namespace App\Infrastructure;

use App\Domain\CalculoUtilidadInterface;

class CalculoUtilidad implements CalculoUtilidadInterface {
    public function calcularUtilidad(int $productoId): float {
        return 25.50;
    }
}
