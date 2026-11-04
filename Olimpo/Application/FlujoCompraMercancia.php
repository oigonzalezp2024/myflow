<?php

namespace Olimpo\Application;

use Olimpo\Domain\CalculoUtilidadInterface;
use Olimpo\Domain\RegistroProductoInterface;
use Olimpo\Domain\TomaFotografiaInterface;
use Olimpo\Domain\CategorizacionInterface;
use Olimpo\Domain\CompraMercanciaInterface;
use Olimpo\Domain\PublicacionProductoInterface;

use Olimpo\Infrastructure\CalculoUtilidad;
use Olimpo\Infrastructure\RegistroProducto;
use Olimpo\Infrastructure\TomaFotografia;
use Olimpo\Infrastructure\Categorizacion;
use Olimpo\Infrastructure\CompraMercancia;
use Olimpo\Infrastructure\PublicacionProducto;

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
