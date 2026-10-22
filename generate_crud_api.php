<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$action = $data['action'] ?? 'generate';

// --- ACCIÓN 2: GUARDAR ARCHIVO EN DISCO ---
if ($action === 'save') {
    $code = $data['code'] ?? '';
    $path = trim($data['filePath'] ?? '', '/\\');
    $fileName = trim($data['fileName'] ?? '');

    if (empty($code) || empty($fileName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El código y el nombre del archivo son obligatorios.']);
        exit();
    }

    // Asegurar extensión .php
    if (!str_ends_with(strtolower($fileName), '.php')) {
        $fileName .= '.php';
    }

    // Determinar la ruta completa del directorio
    $dirPath = __DIR__;
    if (!empty($path)) {
        $dirPath .= DIRECTORY_SEPARATOR . $path;
    }

    // Crear la estructura de carpetas si no existe
    if (!is_dir($dirPath)) {
        if (!mkdir($dirPath, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "No se pudo crear el directorio: {$dirPath}"]);
            exit();
        }
    }

    $fullFilePath = $dirPath . DIRECTORY_SEPARATOR . $fileName;

    // Escribir el archivo
    if (file_put_contents($fullFilePath, $code) !== false) {
        echo json_encode([
            'success' => true,
            'message' => "Archivo creado exitosamente en: {$fullFilePath}",
            'relativePath' => (!empty($path) ? $path . '/' : '') . $fileName
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => "Error al escribir el archivo en {$fullFilePath}"]);
    }
    exit();
}

// --- ACCIÓN 1: GENERAR CÓDIGO CRUD ---
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
    $fields = [];

    foreach ($columns as $col) {
        $fieldName = $col['Field'];
        if ($col['Key'] === 'PRI') {
            $primaryKey = $fieldName;
        } else {
            $fields[] = $fieldName;
        }
    }

    $fieldsList = implode(", ", array_map(fn($f) => "`$f`", $fields));
    $paramsList = implode(", ", array_map(fn($f) => ":$f", $fields));
    $updateList = implode(", ", array_map(fn($f) => "`$f` = :$f", $fields));

    $phpCode = "<?php\n";
    $phpCode .= "// API REST Autogenerada para la tabla '{$tableName}'\n";
    $phpCode .= "header('Content-Type: application/json');\n";
    $phpCode .= "header('Access-Control-Allow-Origin: *');\n";
    $phpCode .= "header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');\n";
    $phpCode .= "header('Access-Control-Allow-Headers: Content-Type');\n\n";
    $phpCode .= "if (\$_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }\n\n";
    $phpCode .= "\$pdo = new PDO('mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4', '{$username}', '{$password}', [\n";
    $phpCode .= "    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
    $phpCode .= "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC\n";
    $phpCode .= "]);\n\n";
    $phpCode .= "\$method = \$_SERVER['REQUEST_METHOD'];\n";
    $phpCode .= "\$input = json_decode(file_get_contents('php://input'), true);\n";
    $phpCode .= "\$id = \$_GET['{$primaryKey}'] ?? null;\n\n";
    $phpCode .= "switch (\$method) {\n";
    $phpCode .= "    case 'GET':\n";
    $phpCode .= "        if (\$id) {\n";
    $phpCode .= "            \$stmt = \$pdo->prepare('SELECT * FROM `{$tableName}` WHERE `{$primaryKey}` = ?');\n";
    $phpCode .= "            \$stmt->execute([\$id]);\n";
    $phpCode .= "            echo json_encode(\$stmt->fetch() ?: ['message' => 'Registro no encontrado']);\n";
    $phpCode .= "        } else {\n";
    $phpCode .= "            \$stmt = \$pdo->query('SELECT * FROM `{$tableName}`');\n";
    $phpCode .= "            echo json_encode(\$stmt->fetchAll());\n";
    $phpCode .= "        }\n";
    $phpCode .= "        break;\n\n";
    $phpCode .= "    case 'POST':\n";
    $phpCode .= "        \$sql = 'INSERT INTO `{$tableName}` ({$fieldsList}) VALUES ({$paramsList})';\n";
    $phpCode .= "        \$stmt = \$pdo->prepare(\$sql);\n";
    $phpCode .= "        \$stmt->execute(\$input);\n";
    $phpCode .= "        echo json_encode(['success' => true, '{$primaryKey}' => \$pdo->lastInsertId()]);\n";
    $phpCode .= "        break;\n\n";
    $phpCode .= "    case 'PUT':\n";
    $phpCode .= "        if (!\$id) { echo json_encode(['error' => 'Se requiere el campo {$primaryKey}']); break; }\n";
    $phpCode .= "        \$sql = 'UPDATE `{$tableName}` SET {$updateList} WHERE `{$primaryKey}` = :{$primaryKey}';\n";
    $phpCode .= "        \$input['{$primaryKey}'] = \$id;\n";
    $phpCode .= "        \$stmt = \$pdo->prepare(\$sql);\n";
    $phpCode .= "        \$stmt->execute(\$input);\n";
    $phpCode .= "        echo json_encode(['success' => true, 'updated' => \$stmt->rowCount()]);\n";
    $phpCode .= "        break;\n\n";
    $phpCode .= "    case 'DELETE':\n";
    $phpCode .= "        if (!\$id) { echo json_encode(['error' => 'Se requiere el campo {$primaryKey}']); break; }\n";
    $phpCode .= "        \$stmt = \$pdo->prepare('DELETE FROM `{$tableName}` WHERE `{$primaryKey}` = ?');\n";
    $phpCode .= "        \$stmt->execute([\$id]);\n";
    $phpCode .= "        echo json_encode(['success' => true, 'deleted' => \$stmt->rowCount()]);\n";
    $phpCode .= "        break;\n";
    $phpCode .= "}\n";

    // Sugerencia de nombre de archivo por defecto
    $defaultFileName = ucfirst($tableName) . "Api.php";

    echo json_encode([
        'success' => true,
        'message' => "Estructura de '{$tableName}' analizada correctamente.",
        'primaryKey' => $primaryKey,
        'defaultFileName' => $defaultFileName,
        'generatedCode' => $phpCode
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al analizar la estructura: ' . $e->getMessage()
    ]);
}
