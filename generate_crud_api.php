<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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

    if (!str_ends_with(strtolower($fileName), '.php') && !str_ends_with(strtolower($fileName), '.js')) {
        $fileName .= '.php';
    }

    $dirPath = __DIR__;
    if (!empty($path)) {
        $dirPath .= DIRECTORY_SEPARATOR . $path;
    }

    if (!is_dir($dirPath)) {
        if (!mkdir($dirPath, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "No se pudo crear el directorio: {$dirPath}"]);
            exit();
        }
    }

    $fullFilePath = $dirPath . DIRECTORY_SEPARATOR . $fileName;

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

// --- ACCIÓN 3: GENERAR CÓDIGO DEL CLIENTE API ---
if ($action === 'generate_client') {
    $endpointUrl = $data['endpointUrl'] ?? 'http://localhost/api/MiApi.php';
    $token = $data['token'] ?? 'aqui_tu_token_seguro';
    $primaryKey = $data['primaryKey'] ?? 'id';
    $clientLanguage = $data['clientLanguage'] ?? 'js'; // 'js' o 'php'

    if ($clientLanguage === 'js') {
        $clientCode = "/**\n";
        $clientCode .= " * Cliente JavaScript autogenerado para consumir la API\n";
        $clientCode .= " * Endpoint: {$endpointUrl}\n";
        $clientCode .= " */\n\n";
        $clientCode .= "class ApiClient {\n";
        $clientCode .= "    constructor() {\n";
        $clientCode .= "        this.baseUrl = '{$endpointUrl}';\n";
        $clientCode .= "        this.token = '{$token}';\n";
        $clientCode .= "        this.primaryKey = '{$primaryKey}';\n";
        $clientCode .= "    }\n\n";
        $clientCode .= "    async request(method = 'GET', data = null, id = null) {\n";
        $clientCode .= "        let url = this.baseUrl;\n";
        $clientCode .= "        if (id !== null) {\n";
        $clientCode .= "            url += `?\${this.primaryKey}=\${id}`;\n";
        $clientCode .= "        }\n\n";
        $clientCode .= "        const options = {\n";
        $clientCode .= "            method: method,\n";
        $clientCode .= "            headers: {\n";
        $clientCode .= "                'Content-Type': 'application/json',\n";
        $clientCode .= "                'Authorization': `Bearer \${this.token}`\n";
        $clientCode .= "            }\n";
        $clientCode .= "        };\n\n";
        $clientCode .= "        if (data && (method === 'POST' || method === 'PUT')) {\n";
        $clientCode .= "            options.body = JSON.stringify(data);\n";
        $clientCode .= "        }\n\n";
        $clientCode .= "        try {\n";
        $clientCode .= "            const response = await fetch(url, options);\n";
        $clientCode .= "            return await response.json();\n";
        $clientCode .= "        } catch (error) {\n";
        $clientCode .= "            console.error('Error en cliente API:', error);\n";
        $clientCode .= "            return { success: false, message: error.message };\n";
        $clientCode .= "        }\n";
        $clientCode .= "    }\n\n";
        $clientCode .= "    getAll() { return this.request('GET'); }\n";
        $clientCode .= "    getById(id) { return this.request('GET', null, id); }\n";
        $clientCode .= "    create(data) { return this.request('POST', data); }\n";
        $clientCode .= "    update(id, data) { return this.request('PUT', data, id); }\n";
        $clientCode .= "    delete(id) { return this.request('DELETE', null, id); }\n";
        $clientCode .= "}\n";

        $defaultFileName = "ApiClient.js";
    } else {
        $clientCode = "<?php\n";
        $clientCode .= "/**\n";
        $clientCode .= " * Cliente PHP cURL autogenerado para consumir la API\n";
        $clientCode .= " * Endpoint: {$endpointUrl}\n";
        $clientCode .= " */\n\n";
        $clientCode .= "class ApiClientPHP {\n";
        $clientCode .= "    private \$baseUrl;\n";
        $clientCode .= "    private \$token;\n";
        $clientCode .= "    private \$primaryKey;\n\n";
        $clientCode .= "    public function __construct(\$baseUrl = '{$endpointUrl}', \$token = '{$token}', \$primaryKey = '{$primaryKey}') {\n";
        $clientCode .= "        \$this->baseUrl = \$baseUrl;\n";
        $clientCode .= "        \$this->token = \$token;\n";
        $clientCode .= "        \$this->primaryKey = \$primaryKey;\n";
        $clientCode .= "    }\n\n";
        $clientCode .= "    private function send(\$method, \$data = null, \$id = null) {\n";
        $clientCode .= "        \$url = \$this->baseUrl;\n";
        $clientCode .= "        if (\$id !== null) {\n";
        $clientCode .= "            \$url .= '?' . \$this->primaryKey . '=' . \$id;\n";
        $clientCode .= "        }\n\n";
        $clientCode .= "        \$ch = curl_init(\$url);\n";
        $clientCode .= "        \$headers = [\n";
        $clientCode .= "            'Content-Type: application/json',\n";
        $clientCode .= "            'Authorization: Bearer ' . \$this->token\n";
        $clientCode .= "        ];\n\n";
        $clientCode .= "        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \$method);\n";
        $clientCode .= "        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n";
        $clientCode .= "        curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n\n";
        $clientCode .= "        if (\$data !== null && (\$method === 'POST' || \$method === 'PUT')) {\n";
        $clientCode .= "            curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$data));\n";
        $clientCode .= "        }\n\n";
        $clientCode .= "        \$response = curl_exec(\$ch);\n";
        $clientCode .= "        curl_close(\$ch);\n";
        $clientCode .= "        return json_decode(\$response, true);\n";
        $clientCode .= "    }\n\n";
        $clientCode .= "    public function getAll() { return \$this->send('GET'); }\n";
        $clientCode .= "    public function getById(\$id) { return \$this->send('GET', null, \$id); }\n";
        $clientCode .= "    public function create(\$data) { return \$this->send('POST', \$data); }\n";
        $clientCode .= "    public function update(\$id, \$data) { return \$this->send('PUT', \$data, \$id); }\n";
        $clientCode .= "    public function delete(\$id) { return \$this->send('DELETE', null, \$id); }\n";
        $clientCode .= "}\n";

        $defaultFileName = "ApiClient.php";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Código de cliente generado exitosamente.',
        'generatedCode' => $clientCode,
        'defaultFileName' => $defaultFileName
    ]);
    exit();
}

// --- ACCIÓN 1: GENERAR CÓDIGO CRUD CON TOKEN ---
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

    // Generar un token por defecto único basado en el nombre de la tabla
    $defaultToken = "token_" . md5($tableName . time());

    // Estructura del código PHP que se va a crear/guardar en disco con validación de Token
    $phpCode = "<?php\n";
    $phpCode .= "// API REST Autogenerada con Token para la tabla '{$tableName}'\n";
    $phpCode .= "header('Content-Type: application/json');\n";
    $phpCode .= "header('Access-Control-Allow-Origin: *');\n";
    $phpCode .= "header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');\n";
    $phpCode .= "header('Access-Control-Allow-Headers: Content-Type, Authorization');\n\n";
    $phpCode .= "if (\$_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }\n\n";
    
    // Definición del Token de Seguridad integrado en el archivo
    $phpCode .= "// Token de acceso requerido en la cabecera Authorization: Bearer <token>\n";
    $phpCode .= "define('API_TOKEN', '{$defaultToken}');\n\n";
    
    // Lógica de validación de la cabecera Bearer Token
    $phpCode .= "\$headers = apache_request_headers();\n";
    $phpCode .= "\$authHeader = \$headers['Authorization'] ?? \$headers['authorization'] ?? '';\n";
    $phpCode .= "\$tokenValido = false;\n\n";
    $phpCode .= "if (preg_match('/Bearer\s(\S+)/', \$authHeader, \$matches)) {\n";
    $phpCode .= "    if (\$matches[1] === API_TOKEN) {\n";
    $phpCode .= "        \$tokenValido = true;\n";
    $phpCode .= "    }\n";
    $phpCode .= "}\n\n";
    $phpCode .= "if (!\$tokenValido) {\n";
    $phpCode .= "    http_response_code(401);\n";
    $phpCode .= "    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado. Token inválido o ausente.']);\n";
    $phpCode .= "    exit();\n";
    $phpCode .= "}\n\n";

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

    $defaultFileName = ucfirst($tableName) . "Api.php";

    echo json_encode([
        'success' => true,
        'message' => "Estructura de '{$tableName}' analizada correctamente con seguridad de Token.",
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
