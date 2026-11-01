<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Domain\Entities\Category;
use App\Domain\Entities\Product;

use App\Domain\Interface\Infrastructure\Persistence\TransactionManagerInterface;
use App\Domain\Interface\Infrastructure\Repositories\CategoryRepositoryInterface;
use App\Domain\Interface\Infrastructure\Repositories\ProductRepositoryInterface;

use App\Infrastructure\Persistence\PdoTransactionManager;
use App\Infrastructure\Repositories\CategoryRepository;
use App\Infrastructure\Repositories\ProductRepository;

use App\Application\UseCases\CreateProductUseCase;

use App\Presentation\ProductApiController;
use App\Presentation\ProductConsoleCommand;

use App\Test\Mock\PDOStatementMock;
use App\Test\Mock\PDOMock;

// ==========================================
// 6. PUNTO DE ENTRADA Y PRUEBA (Simulación)
// ==========================================

$pdo = new PDOMock();
$transactionManager = new PdoTransactionManager($pdo);
$categoryRepo = new CategoryRepository($pdo);
$productRepo = new ProductRepository($pdo);
$crearProductoUseCase = new CreateProductUseCase($transactionManager, $categoryRepo, $productRepo);

// Ejecutamos CLI
$cliCommand = new ProductConsoleCommand($crearProductoUseCase);
$cliCommand->run($_argv ?? ['script.php', 'Electrónica', 'Electrolavadora']);

echo "\n----------------------------------------\n\n";

// Simulamos API REST de forma limpia sin romper cabeceras
$apiController = new ProductApiController($crearProductoUseCase);
echo "=> Simulando respuesta API REST:\n";
$apiController->handleRequest([
    'category_name' => 'Electrónica',
    'product_name' => 'Smart TV 4K'
]);
