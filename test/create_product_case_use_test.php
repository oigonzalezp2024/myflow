<?php

declare(strict_types=1);

// ==========================================
// 1. CAPA DE DOMINIO (Entidades Hiperestrictas)
// ==========================================

class Category
{
    private ?int $id;
    private string $name;

    public function __construct(string $name, ?int $id = null)
    {
        $this->setName($name);
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function setName(string $name): void
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $name));

        if ($trimmed === '') {
            throw new InvalidArgumentException("El nombre de la categoría no puede estar vacío.");
        }

        $length = mb_strlen($trimmed);
        if ($length < 3 || $length > 100) {
            throw new InvalidArgumentException("El nombre de la categoría debe tener entre 3 y 100 caracteres. (Longitud actual: {$length})");
        }

        if (!preg_match('/^[\p{L}\p{N}\s\-]+$/u', $trimmed)) {
            throw new InvalidArgumentException("El nombre de la categoría contiene caracteres no permitidos.");
        }

        $this->name = $trimmed;
    }
}

class Product
{
    private ?int $id;
    private Category $category;
    private string $name;

    public function __construct(Category $category, string $name, ?int $id = null)
    {
        $this->category = $category;
        $this->setName($name);
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function setName(string $name): void
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $name));

        if ($trimmed === '') {
            throw new InvalidArgumentException("El nombre del producto no puede estar vacío.");
        }

        $length = mb_strlen($trimmed);
        if ($length < 2 || $length > 150) {
            throw new InvalidArgumentException("El nombre del producto debe tener entre 2 y 150 caracteres. (Longitud actual: {$length})");
        }

        if (!preg_match('/^[\p{L}\p{N}\s\-\.]+$/u', $trimmed)) {
            throw new InvalidArgumentException("El nombre del producto contiene caracteres no permitidos.");
        }

        $this->name = $trimmed;
    }
}

interface CategoryRepositoryInterface
{
    public function findByName(string $name): ?Category;
    public function create(Category $category): Category;
}

interface ProductRepositoryInterface
{
    public function create(Product $product): Product;
}

interface TransactionManagerInterface
{
    public function transactional(callable $callback): mixed;
}

// ==========================================
// 2. CAPA DE INFRAESTRUCTURA (Repositorios y Transacciones PDO)
// ==========================================

class CategoryRepository implements CategoryRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByName(string $name): ?Category
    {
        $stmt = $this->pdo->prepare("SELECT id, name FROM categories WHERE name = :name LIMIT 1");
        $paramName = trim($name);
        $stmt->bindParam(':name', $paramName, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new Category($row['name'], (int) $row['id']);
    }

    public function create(Category $category): Category
    {
        $stmt = $this->pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
        $categoryName = $category->getName();
        $stmt->bindParam(':name', $categoryName, PDO::PARAM_STR);
        $stmt->execute();

        return new Category($category->getName(), (int) $this->pdo->lastInsertId());
    }
}

class ProductRepository implements ProductRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Product $product): Product
    {
        $category = $product->getCategory();
        if ($category->getId() === null) {
            throw new LogicException("La categoría debe estar persistida antes de asociarle un producto.");
        }

        $stmt = $this->pdo->prepare("INSERT INTO products (category_id, name) VALUES (:category_id, :name)");

        $categoryId = $category->getId();
        $productName = $product->getName();

        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $productName, PDO::PARAM_STR);

        $stmt->execute();

        return new Product($category, $product->getName(), (int) $this->pdo->lastInsertId());
    }
}

class PdoTransactionManager implements TransactionManagerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function transactional(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

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
