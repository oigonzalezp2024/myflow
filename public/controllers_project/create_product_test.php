<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Infrastructure\Persistence\PdoTransactionManager;
use App\Infrastructure\Repositories\CategoryRepository;
use App\Infrastructure\Repositories\ProductRepository;
use App\Application\UseCases\CreateProductUseCase;
use App\Presentation\ProductApiController;
use App\Test\Mock\PDOMock;

// ==========================================
// PUNTO DE ENTRADA ÚNICO (API REST)
// ==========================================

// 1. Instanciación de dependencias
// $pdo = new PDOMock();
$pdo = new PDO('mysql:host=localhost;port=3306;dbname=db_test;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$transactionManager = new PdoTransactionManager($pdo);
$categoryRepo = new CategoryRepository($pdo);
$productRepo = new ProductRepository($pdo);

$createProductUseCase = new CreateProductUseCase(
    $transactionManager, 
    $categoryRepo, 
    $productRepo
);

// 2. Inicializar el controlador
$apiController = new ProductApiController($createProductUseCase);

// 3. Ejecutar la petición (La clase se encargará internamente de leer php://input en formato JSON)
$apiController->handleRequest();
