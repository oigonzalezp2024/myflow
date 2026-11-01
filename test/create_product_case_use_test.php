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

// ==========================================
// 3. DOBLES DE PRUEBA (MOCKS DE PDO Y PDOSTATEMENT)
// ==========================================

class PDOStatementMock extends PDOStatement
{
    private array $fetchData;
    private int $fetchIndex = 0;
    private array $boundValues = [];

    public function __construct(array $fetchData = [])
    {
        $this->fetchData = $fetchData;
    }

    public function bindParam($param, &$variable, $type = PDO::PARAM_STR, $len = null, $driverOptions = null): bool
    {
        $this->boundValues[$param] = $variable;
        return true;
    }

    public function execute($inputParameters = null): bool
    {
        return true;
    }

    public function fetch($mode = PDO::FETCH_DEFAULT, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0): mixed
    {
        if (isset($this->fetchData[$this->fetchIndex])) {
            return $this->fetchData[$this->fetchIndex++];
        }
        return false;
    }
}

class PDOMock extends PDO
{
    private int $autoIncrementId = 10;

    public function __construct()
    {
        // Evita conexión real
    }

    public function beginTransaction(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    public function rollBack(): bool
    {
        return true;
    }

    public function inTransaction(): bool
    {
        return false;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        if (str_contains($query, "SELECT id, name FROM categories")) {
            return new PDOStatementMock([
                ['id' => 11, 'name' => 'Electrónica']
            ]);
        }

        return new PDOStatementMock();
    }

    public function lastInsertId(string|null $name = null): string|false
    {
        return (string) (++$this->autoIncrementId);
    }
}

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
