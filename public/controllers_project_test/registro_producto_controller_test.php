<?php

declare(strict_types=1);

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

use App\Application\FlujoCompraMercancia;

require_once __DIR__ . '/../../vendor/autoload.php';

// Inicializar entorno si usas .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

try {
    // 1. Instanciar dependencias de infraestructura
    $compra = new CompraMercancia();
    $registro = new RegistroProducto();
    $fotografia = new TomaFotografia();
    $categorizacion = new Categorizacion();
    $calculoUtilidad = new CalculoUtilidad();
    $publicacion = new PublicacionProducto();

    // 2. Inyección en el orquestador de aplicación
    $flujoCompra = new FlujoCompraMercancia(
        $compra, $registro, $fotografia, $categorizacion, $calculoUtilidad, $publicacion
    );

    // 3. Ejecución del Paso 1 (Operario)
    $productoId = $flujoCompra->pasoInicialOperario(
        ['proveedor' => 'XYZ', 'monto' => 150.00], 
        ['nombre' => 'Taladro Percutor', 'stock' => 10], 
        '/uploads/fotos/taladro.jpg'
    );

    // 4. Ejecución del Paso 2 (Superior)
    $flujoCompra->pasoRevisionSuperior($productoId, 3, 12);

    echo json_encode([
        'status' => 'success',
        'message' => "¡Flujo ejecutado con éxito para el producto ID: {$productoId}!"
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
