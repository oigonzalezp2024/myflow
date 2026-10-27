<?php
declare(strict_types=1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * ==========================================
 * 1. SEGURIDAD: TOKEN DE ACCESO AL INTERMEDIARIO
 * ==========================================
 */
$expectedIntermediaryToken = 'TU_TOKEN_INTERMEDIARIO_SECRETO'; 

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$clientToken = '';

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $clientToken = $matches[1];
}

if (!hash_equals($expectedIntermediaryToken, $clientToken)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Token de acceso al intermediario inválido o ausente.'
    ]);
    exit();
}

/**
 * ==========================================
 * 2. PROCESAMIENTO DE LA SOLICITUD
 * ==========================================
 */
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = $data['action'] ?? 'generate_consumer';

$api_path = $data['api_path'] ?? 'clients/ApiClient.php';
// Configuración de conexión a la base de datos
$host     = $data['host'] ?? '127.0.0.1';
$db       = $data['db'] ?? 'myflow';
$user     = $data['user'] ?? 'root';
$pass     = $data['pass'] ?? '';
$port     = $data['port'] ?? '3306';
$charset  = $data['charset'] ?? 'utf8mb4';

if ($action === 'generate_consumer') {
    $endpointUrl = $data['endpointUrl'] ?? 'http://localhost/api/MiApi.php';
    
    // Token que usará el script generado para comunicarse con el servicio final
    $targetServiceToken = $data['targetServiceToken'] ?? 'aqui_tu_token_para_el_servicio_final';
    
    $targetMethod = $data['targetMethod'] ?? 'getAll'; 
    $tableName = $data['tableName'] ?? 'users'; 
    
    $defaultFileName = 'consume_' . $tableName . '.php';
    $outputPath = $data['outputPath'] ?? __DIR__ . '/' . $defaultFileName;

    $primaryKey = 'id';
    $tableColumns = [];

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Obtener la estructura de columnas de la tabla de forma dinámica
        $stmtCols = $pdo->prepare("SHOW COLUMNS FROM `$tableName`");
        $stmtCols->execute();
        $columns = $stmtCols->fetchAll();

        foreach ($columns as $col) {
            $fieldName = $col['Field'];
            $isPrimaryKey = ($col['Key'] === 'PRI');

            if ($isPrimaryKey) {
                $primaryKey = $fieldName;
            }
            
            $tableColumns[] = $fieldName;
        }

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al conectar o consultar la estructura de la base de datos: ' . $e->getMessage()
        ]);
        exit();
    }

    // Construir un código de consumo robusto y listo para producción
    $consumerCode = "<?php\n";
    $consumerCode .= "declare(strict_types=1);\n\n";
    $consumerCode .= "header('Content-Type: application/json; charset=utf-8');\n\n";
    $consumerCode .= "require_once __DIR__ . '/../{$api_path}';\n\n";
    $consumerCode .= "// 1. Configuración de conexión y credenciales\n";
    $consumerCode .= "\$baseUrl = '{$endpointUrl}';\n";
    $consumerCode .= "\$token = '{$targetServiceToken}';\n";
    $consumerCode .= "\$primaryKey = '{$primaryKey}';\n\n";
    $consumerCode .= "\$api = new ApiClient(\$baseUrl, \$token, \$primaryKey);\n\n";
    $consumerCode .= "// 2. Capturar datos entrantes (Soporta JSON o FormData / x-www-form-urlencoded)\n";
    $consumerCode .= "\$inputData = json_decode(file_get_contents('php://input'), true);\n";
    $consumerCode .= "\$requestData = !empty(\$inputData) ? \$inputData : \$_POST;\n\n";

    // Si el método no es delete, filtramos las columnas del payload
    if ($targetMethod !== 'delete') {
        $consumerCode .= "if (empty(\$requestData)) {\n";
        $consumerCode .= "    http_response_code(400);\n";
        $consumerCode .= "    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en la solicitud.']);\n";
        $consumerCode .= "    exit();\n";
        $consumerCode .= "}\n\n";
        $consumerCode .= "// 3. Mapeo y filtrado de columnas permitidas en la tabla `{$tableName}`\n";
        $consumerCode .= "\$allowedColumns = " . var_export($tableColumns, true) . ";\n";
        $consumerCode .= "\$payload = [];\n\n";
        $consumerCode .= "foreach (\$allowedColumns as \$col) {\n";
        $consumerCode .= "    if (\$col === \$primaryKey) continue; // La PK generalmente se autoincrementa\n";
        $consumerCode .= "    if (isset(\$requestData[\$col])) {\n";
        $consumerCode .= "        \$payload[\$col] = \$requestData[\$col];\n";
        $consumerCode .= "    }\n";
        $consumerCode .= "}\n\n";
    }
    
    // Inyectar la lógica según el método objetivo
    if ($targetMethod === 'create') {
        $consumerCode .= "// 4. Ejecución de creación hacia el API final\n";
        $consumerCode .= "try {\n";
        $consumerCode .= "    \$response = \$api->create(\$payload);\n";
        $consumerCode .= "    http_response_code(200);\n";
        $consumerCode .= "    echo json_encode(['success' => true, 'data' => \$response]);\n";
        $consumerCode .= "} catch (\\Exception \$e) {\n";
        $consumerCode .= "    http_response_code(500);\n";
        $consumerCode .= "    echo json_encode(['success' => false, 'message' => \$e->getMessage()]);\n";
        $consumerCode .= "}\n\n";
    } elseif ($targetMethod === 'update') {
        $consumerCode .= "\$id = \$requestData[\$primaryKey] ?? null;\n";
        $consumerCode .= "if (!\$id) {\n";
        $consumerCode .= "    http_response_code(400);\n";
        $consumerCode .= "    echo json_encode(['success' => false, 'message' => 'Falta el identificador primario para actualizar.']);\n";
        $consumerCode .= "    exit();\n";
        $consumerCode .= "}\n";
        $consumerCode .= "try {\n";
        $consumerCode .= "    \$response = \$api->update(\$id, \$payload);\n";
        $consumerCode .= "    http_response_code(200);\n";
        $consumerCode .= "    echo json_encode(['success' => true, 'data' => \$response]);\n";
        $consumerCode .= "} catch (\\Exception \$e) {\n";
        $consumerCode .= "    http_response_code(500);\n";
        $consumerCode .= "    echo json_encode(['success' => false, 'message' => \$e->getMessage()]);\n";
        $consumerCode .= "}\n";
    } elseif ($targetMethod === 'delete') {
        $consumerCode .= "\$id = \$requestData[\$primaryKey] ?? \$_GET[\$primaryKey] ?? null;\n";
        $consumerCode .= "if (!\$id) {\n";
        $consumerCode .= "    http_response_code(400);\n";
        $consumerCode .= "    echo json_encode(['success' => false, 'message' => 'Falta el identificador primario para eliminar.']);\n";
        $consumerCode .= "    exit();\n";
        $consumerCode .= "}\n";
        $consumerCode .= "try {\n";
        $consumerCode .= "    \$response = \$api->delete(\$id);\n";
        $consumerCode .= "    http_response_code(200);\n";
        $consumerCode .= "    echo json_encode(['success' => true, 'data' => \$response]);\n";
        $consumerCode .= "} catch (\\Exception \$e) {\n";
        $consumerCode .= "    http_response_code(500);\n";
        $consumerCode .= "    echo json_encode(['success' => false, 'message' => \$e->getMessage()]);\n";
        $consumerCode .= "}\n";
    } elseif ($targetMethod === 'getAll') {
        $consumerCode = "<?php\n";
        $consumerCode .= "declare(strict_types=1);\n\n";
        $consumerCode .= "header('Content-Type: application/json; charset=utf-8');\n\n";
        $consumerCode .= "require_once __DIR__ . '/../{$api_path}';\n\n";
        $consumerCode .= "// 1. Configuración de conexión y credenciales\n";
        $consumerCode .= "\$baseUrl = '{$endpointUrl}';\n";
        $consumerCode .= "\$token = '{$targetServiceToken}';\n";
        $consumerCode .= "\$primaryKey = '{$primaryKey}';\n\n";
        $consumerCode .= "\$api = new ApiClient(\$baseUrl, \$token, \$primaryKey);\n\n";
        $consumerCode .= "\$response = \$api->getAll();\n";
        $consumerCode .= "echo json_encode(['success' => true, 'data' => \$response]);\n\n";
    } elseif ($targetMethod === 'getById') {
        $consumerCode = "<?php\n";
        $consumerCode .= "declare(strict_types=1);\n\n";
        $consumerCode .= "header('Content-Type: application/json; charset=utf-8');\n\n";
        $consumerCode .= "require_once __DIR__ . '/../{$api_path}';\n\n";
        $consumerCode .= "// 1. Configuración de conexión y credenciales\n";
        $consumerCode .= "\$baseUrl = '{$endpointUrl}';\n";
        $consumerCode .= "\$token = '{$targetServiceToken}';\n";
        $consumerCode .= "\$primaryKey = '{$primaryKey}';\n\n";
        $consumerCode .= "\$api = new ApiClient(\$baseUrl, \$token, \$primaryKey);\n\n";
        $consumerCode .= "// 2. Capturar datos entrantes (Soporta JSON o FormData / x-www-form-urlencoded)\n";
        $consumerCode .= "\$inputData = json_decode(file_get_contents('php://input'), true);\n";
        $consumerCode .= "\$requestData = !empty(\$inputData) ? \$inputData : \$_GET;\n\n";
        $consumerCode .= "if (empty(\$requestData)) {\n";
        $consumerCode .= "    http_response_code(400);\n";
        $consumerCode .= "    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en la solicitud.']);\n";
        $consumerCode .= "    exit();\n";
        $consumerCode .= "}\n\n";
        $consumerCode .= "// 3. Mapeo y filtrado de columnas permitidas en la tabla `{$tableName}`\n";
        $consumerCode .= "\$allowedColumns = " . var_export($tableColumns, true) . ";\n";
        $consumerCode .= "\$payload = [];\n\n";
        $consumerCode .= "foreach (\$allowedColumns as \$col) {\n";
        $consumerCode .= "    if (isset(\$requestData[\$col])) {\n";
        $consumerCode .= "        \$payload[\$col] = \$requestData[\$col];\n";
        $consumerCode .= "    }\n";
        $consumerCode .= "}\n\n";
        $consumerCode .= "\$response = \$api->getById(\$payload[\$primaryKey]);\n";
        $consumerCode .= "echo json_encode(['success' => true, 'data' => \$response]);\n";
    } else {
        // Fallback por defecto si es consulta general
        $consumerCode .= "\$response = \$api->getAll();\n";
        $consumerCode .= "echo json_encode(['success' => true, 'data' => \$response]);\n";
    }

    $consumerCode .= "exit;\n";

    if (is_dir($outputPath)) {
        $outputPath = rtrim($outputPath, '/\\') . '/' . $defaultFileName;
    }

    $fileSaved = file_put_contents($outputPath, $consumerCode);

    if ($fileSaved === false) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo guardar el archivo en la ruta especificada: ' . $outputPath
        ]);
        exit();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Servicio intermediario ejecutado con éxito. Archivo de consumo generado.',
        'savedPath' => $outputPath,
        'detectedPrimaryKey' => $primaryKey,
        'detectedColumns' => $tableColumns,
        'generatedCode' => $consumerCode
    ]);
    exit();
}
