<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Domain\Entities\Category;
use App\Domain\Entities\Product;
use App\Domain\Interface\Infrastructure\Repositories\CategoryRepositoryInterface;
use App\Domain\Interface\Infrastructure\Repositories\ProductRepositoryInterface;
use App\Domain\Interface\Infrastructure\Persistence\TransactionManagerInterface;

use App\Infrastructure\Persistence\PdoTransactionManager;
use App\Infrastructure\Repositories\CategoryRepository;
use App\Infrastructure\Repositories\ProductRepository;

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
// 4. CAPA DE APLICACIÓN (Caso de Uso)
// ==========================================

class CrearProductoCaseUse
{
    private TransactionManagerInterface $transactionManager;
    private CategoryRepositoryInterface $categoryRepo;
    private ProductRepositoryInterface $productRepo;

    public function __construct(
        TransactionManagerInterface $transactionManager, 
        CategoryRepositoryInterface $categoryRepo, 
        ProductRepositoryInterface $productRepo
    ) {
        $this->transactionManager = $transactionManager;
        $this->categoryRepo = $categoryRepo;
        $this->productRepo = $productRepo;
    }

    public function ejecutar(string $categoryName, string $productName): array
    {
        return $this->transactionManager->transactional(function () use ($categoryName, $productName) {
            $categorySaved = $this->categoryRepo->findByName($categoryName);

            if ($categorySaved === null) {
                $newCategory = new Category($categoryName);
                $categorySaved = $this->categoryRepo->create($newCategory);
            }

            $product = new Product($categorySaved, $productName);
            $productSaved = $this->productRepo->create($product);

            return [$categorySaved, $productSaved];
        });
    }
}

// ==========================================
// 5. CAPA DE PRESENTACIÓN (API REST y CLI)
// ==========================================

// --- 5.1. Presentación vía API REST (Controlador) ---
class ProductApiController
{
    private CrearProductoCaseUse $crearProductoUseCase;

    public function __construct(CrearProductoCaseUse $crearProductoUseCase)
    {
        $this->crearProductoUseCase = $crearProductoUseCase;
    }

    public function handleRequest(array $requestData): void
    {
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $categoryName = $requestData['category_name'] ?? '';
            $productName = $requestData['product_name'] ?? '';

            [$category, $product] = $this->crearProductoUseCase->ejecutar($categoryName, $productName);

            if (php_sapi_name() !== 'cli') {
                http_response_code(201);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Producto creado con éxito',
                'data' => [
                    'category' => ['id' => $category->getId(), 'name' => $category->getName()],
                    'product' => ['id' => $product->getId(), 'name' => $product->getName()]
                ]
            ], JSON_UNESCAPED_UNICODE) . "\n";

        } catch (InvalidArgumentException | LogicException $e) {
            if (php_sapi_name() !== 'cli') {
                http_response_code(400);
            }
            echo json_encode([
                'success' => false,
                'error' => 'Error de validación o negocio',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE) . "\n";
        } catch (Exception $e) {
            if (php_sapi_name() !== 'cli') {
                http_response_code(500);
            }
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}

// --- 5.2. Presentación vía CLI (Comando de Consola) ---
class ProductConsoleCommand
{
    private CrearProductoCaseUse $crearProductoUseCase;

    public function __construct(CrearProductoCaseUse $crearProductoUseCase)
    {
        $this->crearProductoUseCase = $crearProductoUseCase;
    }

    public function run(array $argv): void
    {
        $categoryName = $argv[1] ?? 'Electrónica';
        $productName = $argv[2] ?? 'Electrolavadora';

        echo "=> Ejecutando comando de consola...\n";
        echo "Categoría: {$categoryName} | Producto: {$productName}\n\n--- Output ---\n";

        try {
            [$category, $product] = $this->crearProductoUseCase->ejecutar($categoryName, $productName);

            echo "¡Proceso exitoso!\n";
            echo "- Categoría ID: " . $category->getId() . " ({$category->getName()})\n";
            echo "- Producto ID: " . $product->getId() . " ({$product->getName()})\n";

        } catch (InvalidArgumentException | LogicException $e) {
            echo "Error de negocio: " . $e->getMessage() . "\n";
            exit(1);
        } catch (Exception $e) {
            echo "Error crítico: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

// ==========================================
// 6. PUNTO DE ENTRADA Y PRUEBA (Simulación)
// ==========================================

$pdo = new PDOMock();
$transactionManager = new PdoTransactionManager($pdo);
$categoryRepo = new CategoryRepository($pdo);
$productRepo = new ProductRepository($pdo);
$crearProductoUseCase = new CrearProductoCaseUse($transactionManager, $categoryRepo, $productRepo);

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
