<?php

namespace App\Application;

use App\Domain\CalculoUtilidadInterface;
use App\Domain\RegistroProductoInterface;
use App\Domain\TomaFotografiaInterface;
use App\Domain\CategorizacionInterface;
use App\Domain\CompraMercanciaInterface;
use App\Domain\PublicacionProductoInterface;

use App\Infrastructure\CalculoUtilidad;
use App\Infrastructure\RegistroProducto;
use App\Infrastructure\TomaFotografia;
use App\Infrastructure\Categorizacion;
use App\Infrastructure\CompraMercancia;
use App\Infrastructure\PublicacionProducto;

// --- Clase del Flujo ---

class FlujoCompraMercancia
{
    private CompraMercanciaInterface $compra;
    private RegistroProductoInterface $registro;
    private TomaFotografiaInterface $fotografia;
    private CategorizacionInterface $categorizacion;
    private CalculoUtilidadInterface $calculoUtilidad;
    private PublicacionProductoInterface $publicacion;

    public function __construct(
        CompraMercanciaInterface $compra,
        RegistroProductoInterface $registro,
        TomaFotografiaInterface $fotografia,
        CategorizacionInterface $categorizacion,
        CalculoUtilidadInterface $calculoUtilidad,
        PublicacionProductoInterface $publicacion
    ) {
        $this->compra = $compra;
        $this->registro = $registro;
        $this->fotografia = $fotografia;
        $this->categorizacion = $categorizacion;
        $this->calculoUtilidad = $calculoUtilidad;
        $this->publicacion = $publicacion;
    }

    public function pasoInicialOperario(array $datosCompra, array $datosProducto, string $rutaFoto): int
    {
        $compraId = $this->compra->ejecutarCompra($datosCompra);
        $productoId = $this->registro->registrar($compraId, $datosProducto);
        $this->fotografia->capturarYAsociarFoto($productoId, $rutaFoto);

        return $productoId;
    }

    public function pasoRevisionSuperior(int $productoId, int $categoriaId, int $usuarioSuperiorId): void
    {
        $this->categorizacion->categorizarProducto($productoId, $categoriaId, $usuarioSuperiorId);
        $this->calculoUtilidad->calcularUtilidad($productoId);
        $this->publicacion->publicar($productoId);
    }
}
