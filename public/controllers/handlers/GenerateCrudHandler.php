<?php
class GenerateCrudHandler {
    public static function execute(array $data): void {
        if (!isset($data['host'], $data['username'], $data['dbName'], $data['tableName'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros de conexión o el nombre de la tabla.']);
            exit();
        }

        $host = $data['host'];
        $port = !empty($data['port']) ? (int)$data['port'] : 3306;
        $username = $data['username'];
        $password = $data['password'] ?? '';
        $dbName = $data['dbName'];
        $tableName = $data['tableName'];
        
        // 1. Obtener la ruta enviada desde el formulario o parámetro
        $rawPath = trim($data['filePath'] ?? '', '/\\');
        
        // 2. Normalizar la ruta con separadores válidos
        $targetApiDir = !empty($rawPath) ? rtrim(__DIR__.'/../../'.$rawPath, '/\\') : __DIR__ ;

        // 3. Resolver la raíz del proyecto retrocediendo la estructura del endpoint (ej. /public/api/ -> sube 2 niveles)
        // O tomar la carpeta padre si la ruta apunta directamente a /api/
        $projectRootDir = dirname($targetApiDir, 2); 
        $srcBasePath = $projectRootDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $stmt = $pdo->query("DESCRIBE `{$tableName}`");
            $columns = $stmt->fetchAll();

            if (empty($columns)) {
                throw new Exception("La tabla '{$tableName}' no existe o no tiene columnas.");
            }

            $primaryKey = 'id';
            $entityProperties = "";
            $toArrayBody = "";
            $fields = [];

            foreach ($columns as $col) {
                $fieldName = $col['Field'];
                
                if ($col['Key'] === 'PRI') {
                    $primaryKey = $fieldName;
                } else {
                    $fields[] = $fieldName;
                }

                $entityProperties .= "    private \$field_{$fieldName};\n";
                $toArrayBody .= "            '{$fieldName}' => \$this->field_{$fieldName},\n";
            }

            $pascalName = ucfirst($tableName);

            // ==========================================
            // 1. ENTIDAD (Domain/Entities)
            // ==========================================
            $entityCode = "<?php\nnamespace Domain\\Entities;\n\nclass {$pascalName}Entity {\n{$entityProperties}\n    public function __construct(array \$data) {\n";
            foreach ($columns as $col) {
                $f = $col['Field'];
                $entityCode .= "        \$this->field_{$f} = \$data['{$f}'] ?? null;\n";
            }
            $entityCode .= "    }\n\n    public function toArray(): array {\n        return [\n{$toArrayBody}        ];\n    }\n}\n";

            // ==========================================
            // 2. CONTRATO REPOSITORY (Domain/Repositories)
            // ==========================================
            $interfaceCode = "<?php\nnamespace Domain\\Repositories;\n\nuse Domain\\Entities\\{$pascalName}Entity;\n\ninterface {$pascalName}RepositoryInterface {\n    public function getAll(): array;\n    public function getById(\$id): ?{$pascalName}Entity;\n    public function create(array \$data): int;\n    public function update(\$id, array \$data): int;\n    public function delete(\$id): int;\n}\n";

            // ==========================================
            // 3. REPOSITORY MYSQL (Infrastructure/Persistence)
            // ==========================================
            $fieldsList = implode(", ", array_map(fn($f) => "`$f`", $fields));
            $paramsList = implode(", ", array_map(fn($f) => ":$f", $fields));
            $updateList = implode(", ", array_map(fn($f) => "`$f` = :$f", $fields));

            $repoCode = "<?php\nnamespace Infrastructure\\Persistence;\n\nuse Domain\\Repositories\\{$pascalName}RepositoryInterface;\nuse Domain\\Entities\\{$pascalName}Entity;\nuse PDO;\n\nclass MySQL{$pascalName}Repository implements {$pascalName}RepositoryInterface {\n    private PDO \$pdo;\n\n    public function __construct(PDO \$pdo) {\n        \$this->pdo = \$pdo;\n    }\n\n    public function getAll(): array {\n        \$stmt = \$this->pdo->query('SELECT * FROM `{$tableName}`');\n        \$rows = \$stmt->fetchAll();\n        return array_map(fn(\$row) => new {$pascalName}Entity(\$row), \$rows);\n    }\n\n    public function getById(\$id): ?{$pascalName}Entity {\n        \$stmt = \$this->pdo->prepare('SELECT * FROM `{$tableName}` WHERE `{$primaryKey}` = ?');\n        \$stmt->execute([\$id]);\n        \$row = \$stmt->fetch();\n        return \$row ? new {$pascalName}Entity(\$row) : null;\n    }\n\n    public function create(array \$data): int {\n        \$sql = 'INSERT INTO `{$tableName}` ({$fieldsList}) VALUES ({$paramsList})';\n        \$stmt = \$this->pdo->prepare(\$sql);\n        \$stmt->execute(\$data);\n        return (int)\$this->pdo->lastInsertId();\n    }\n\n    public function update(\$id, array \$data): int {\n        \$sql = 'UPDATE `{$tableName}` SET {$updateList} WHERE `{$primaryKey}` = :{$primaryKey}';\n        \$data['{$primaryKey}'] = \$id;\n        \$stmt = \$this->pdo->prepare(\$sql);\n        \$stmt->execute(\$data);\n        return \$stmt->rowCount();\n    }\n\n    public function delete(\$id): int {\n        \$stmt = \$this->pdo->prepare('DELETE FROM `{$tableName}` WHERE `{$primaryKey}` = ?');\n        \$stmt->execute([\$id]);\n        return \$stmt->rowCount();\n    }\n}\n";

            // ==========================================
            // 4. DTO DEL CASO (Application/DTO)
            // ==========================================
            $dtoCode = "<?php\nnamespace Application\\DTO;\n\nclass {$pascalName}RequestDTO {\n    private array \$payload;\n\n    public function __construct(array \$payload) {\n        \$this->payload = \$payload;\n    }\n\n    public function toArray(): array {\n        return \$this->payload;\n    }\n}\n";

            // ==========================================
            // 5. CASO DE USO (Application/UseCases)
            // ==========================================
            $useCaseCode = "<?php\nnamespace Application\\UseCases;\n\nuse Domain\\Repositories\\{$pascalName}RepositoryInterface;\nuse Application\\DTO\\{$pascalName}RequestDTO;\n\nclass Manage{$pascalName}UseCase {\n    private {$pascalName}RepositoryInterface \$repository;\n\n    public function __construct({$pascalName}RepositoryInterface \$repository) {\n        \$this->repository = \$repository;\n    }\n\n    public function handleList() { return \$this->repository->getAll(); }\n    public function handleGet(\$id) { return \$this->repository->getById(\$id); }\n    public function handleCreate({$pascalName}RequestDTO \$dto) { return \$this->repository->create(\$dto->toArray()); }\n    public function handleUpdate(\$id, {$pascalName}RequestDTO \$dto) { return \$this->repository->update(\$id, \$dto->toArray()); }\n    public function handleDelete(\$id) { return \$this->repository->delete(\$id); }\n}\n";

            // ==========================================
            // ESCRIBIR ARQUITECTURA EN DISCO (EN /src/)
            // ==========================================
            self::writeFile("{$srcBasePath}Domain/Entities/{$pascalName}Entity.php", $entityCode);
            self::writeFile("{$srcBasePath}Domain/Repositories/{$pascalName}RepositoryInterface.php", $interfaceCode);
            self::writeFile("{$srcBasePath}Infrastructure/Persistence/MySQL{$pascalName}Repository.php", $repoCode);
            self::writeFile("{$srcBasePath}Application/DTO/{$pascalName}RequestDTO.php", $dtoCode);
            self::writeFile("{$srcBasePath}Application/UseCases/Manage{$pascalName}UseCase.php", $useCaseCode);

            // ==========================================
            // 6. ORQUESTADOR DE PRESENTACIÓN (Único código retornado)
            // ==========================================
            $defaultToken = "token_" . md5($tableName . time());
            $controllerCode = "<?php\n";
            $controllerCode .= "namespace Presentation\Controllers;\n\n";
            
            // Inclusión manual de dependencias mediante require_once
            $controllerCode .= "require_once __DIR__ . '/../../src/Application/DTO/{$pascalName}RequestDTO.php';\n";
            $controllerCode .= "require_once __DIR__ . '/../../src/Application/UseCases/Manage{$pascalName}UseCase.php';\n";
            $controllerCode .= "require_once __DIR__ . '/../../src/Domain/Entities/{$pascalName}Entity.php';\n";
            $controllerCode .= "require_once __DIR__ . '/../../src/Domain/Repositories/{$pascalName}RepositoryInterface.php';\n";
            $controllerCode .= "require_once __DIR__ . '/../../src/Infrastructure/Persistence/MySQL{$pascalName}Repository.php';\n\n";

            $controllerCode .= "use Domain\Entities\\{$pascalName}Entity;\n";
            $controllerCode .= "use Domain\Repositories\\{$pascalName}RepositoryInterface;\n";
            $controllerCode .= "use Infrastructure\Persistence\MySQL{$pascalName}Repository;\n";
            $controllerCode .= "use Application\UseCases\Manage{$pascalName}UseCase;\n";
            $controllerCode .= "use Application\DTO\\{$pascalName}RequestDTO;\n";
            $controllerCode .= "use PDO;\n\n";
            
            $controllerCode .= "header('Content-Type: application/json');\n";
            $controllerCode .= "header('Access-Control-Allow-Origin: *');\n";
            $controllerCode .= "header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');\n";
            $controllerCode .= "header('Access-Control-Allow-Headers: Content-Type, Authorization');\n\n";
            $controllerCode .= "if (\$_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }\n\n";
            
            $controllerCode .= "define('API_TOKEN', '{$defaultToken}');\n";
            $controllerCode .= "\$headers = apache_request_headers();\n";
            $controllerCode .= "\$authHeader = \$headers['Authorization'] ?? \$headers['authorization'] ?? '';\n";
            $controllerCode .= "if (!preg_match('/Bearer\s(\S+)/', \$authHeader, \$matches) || \$matches[1] !== API_TOKEN) {\n";
            $controllerCode .= "    http_response_code(401);\n";
            $controllerCode .= "    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);\n";
            $controllerCode .= "    exit();\n";
            $controllerCode .= "}\n\n";

            $controllerCode .= "\$pdo = new PDO('mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4', '{$username}', '{$password}', [\n";
            $controllerCode .= "    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
            $controllerCode .= "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC\n";
            $controllerCode .= "]);\n\n";
            
            $controllerCode .= "\$repository = new MySQL{$pascalName}Repository(\$pdo);\n";
            $controllerCode .= "\$useCase = new Manage{$pascalName}UseCase(\$repository);\n\n";
            
            $controllerCode .= "\$method = \$_SERVER['REQUEST_METHOD'];\n";
            $controllerCode .= "\$input = json_decode(file_get_contents('php://input'), true) ?? [];\n";
            $controllerCode .= "\$id = \$_GET['{$primaryKey}'] ?? null;\n\n";
            
            $controllerCode .= "switch (\$method) {\n";
            $controllerCode .= "    case 'GET':\n";
            $controllerCode .= "        if (\$id) {\n";
            $controllerCode .= "            \$entity = \$useCase->handleGet(\$id);\n";
            $controllerCode .= "            echo json_encode(\$entity ? \$entity->toArray() : ['message' => 'Registro no encontrado']);\n";
            $controllerCode .= "        } else {\n";
            $controllerCode .= "            \$entities = \$useCase->handleList();\n";
            $controllerCode .= "            echo json_encode(array_map(fn(\$e) => \$e->toArray(), \$entities));\n";
            $controllerCode .= "        }\n";
            $controllerCode .= "        break;\n";
            $controllerCode .= "    case 'POST':\n";
            $controllerCode .= "        \$dto = new {$pascalName}RequestDTO(\$input);\n";
            $controllerCode .= "        \$insertId = \$useCase->handleCreate(\$dto);\n";
            $controllerCode .= "        echo json_encode(['success' => true, '{$primaryKey}' => \$insertId]);\n";
            $controllerCode .= "        break;\n";
            $controllerCode .= "    case 'PUT':\n";
            $controllerCode .= "        if (!\$id) { echo json_encode(['error' => 'Se requiere el campo {$primaryKey}']); break; }\n";
            $controllerCode .= "        \$dto = new {$pascalName}RequestDTO(\$input);\n";
            $controllerCode .= "        \$updated = \$useCase->handleUpdate(\$id, \$dto);\n";
            $controllerCode .= "        echo json_encode(['success' => true, 'updated' => \$updated]);\n";
            $controllerCode .= "        break;\n";
            $controllerCode .= "    case 'DELETE':\n";
            $controllerCode .= "        if (!\$id) { echo json_encode(['error' => 'Se requiere el campo {$primaryKey}']); break; }\n";
            $controllerCode .= "        \$deleted = \$useCase->handleDelete(\$id);\n";
            $controllerCode .= "        echo json_encode(['success' => true, 'deleted' => \$deleted]);\n";
            $controllerCode .= "        break;\n";
            $controllerCode .= "}\n";

            $defaultFileName = ucfirst($tableName) . "Api.php";

            echo json_encode([
                'success' => true,
                'message' => "Arquitectura guardada en '{$srcBasePath}' y Orquestador generado para '{$tableName}'.",
                'primaryKey' => $primaryKey,
                'defaultFileName' => $defaultFileName,
                'generatedCode' => $controllerCode
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al analizar la estructura o escribir capas: ' . $e->getMessage()
            ]);
        }
    }

    private static function writeFile(string $fullPath, string $content): void {
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, $content);
    }
}
