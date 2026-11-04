<?php
namespace Olimpo\Infrastructure;

use Olimpo\Domain\CalculoUtilidadInterface;

class CalculoUtilidad implements CalculoUtilidadInterface {
    public function calcularUtilidad(int $productoId): float {
        return 25.50;
    }
}
