<?php
namespace App\Domain;

interface CompraMercanciaInterface {
    public function ejecutarCompra(array $datosCompra): int; 
}

interface RegistroProductoInterface {
    public function registrar(int $compraId, array $datosProducto): int; 
}

interface TomaFotografiaInterface {
    public function capturarYAsociarFoto(int $productoId, string $rutaImagen): bool;
}

interface CategorizacionInterface {
    public function categorizarProducto(int $productoId, int $categoriaId, int $usuarioSuperiorId): bool;
}

interface CalculoUtilidadInterface {
    public function calcularUtilidad(int $productoId): float;
}

interface PublicacionProductoInterface {
    public function publicar(int $productoId): bool;
}
