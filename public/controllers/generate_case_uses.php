<?php
// Configuración de cabeceras para CORS
header("Access-Control-Allow-Origin: *"); // AJUSTA ESTO EN PRODUCCIÓN POR SEGURIDAD
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require __DIR__ . '/../clients/ApiClient.php';

// Manejo de la petición OPTIONS (pre-flight request)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener y decodificar los datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar los datos recibidos
if (
    !isset($data['title'], $data['description'], $data['status'], $data['endpointUrl'])
) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $input]);
    exit();
}

$primaryKey            = $data['title'] ?? 'id_proveedor';
$tableName             = $data['tableName'] ?? 'pedidos';
$db                    = $data['db'] ?? 'test_insumos';
$targetServiceEndpoint = $data['targetServiceEndpoint'] ?? 'http://localhost/myflow/public/api/insumos/proveedores/';
$targetServiceTokenVal = $data['targetServiceTokenVal'] ?? 'token_e146b7073904f432bfc121ea3e396708';
$tsClientFilePath      = $data['tsClientFilePath'];

// Token con el que este cliente se autentica ante el API Intermediario
$intermediaryToken = 'TU_TOKEN_INTERMEDIARIO_SECRETO';
$endpointIntermediary = 'http://localhost/myflow/public/controllers/consumo.php';

// Instanciamos el cliente apuntando al intermediario con su respectivo token de acceso
$apiClient = new ApiClient($endpointIntermediary, $intermediaryToken, $primaryKey);

$methods = ['getAll', 'getById', 'create', 'update', 'delete'];

foreach ($methods as $targetMethod) {
    $data = [];
    $data['host'] = 'localhost';
    $data['db'] = $db;
    $data['action'] = 'generate_consumer';
    $data['endpointUrl'] = $targetServiceEndpoint;
    
    // Token que el intermediario inyectará en el archivo PHP resultante
    $data['targetServiceToken'] = $targetServiceTokenVal; 
    
    $data['targetMethod'] = $targetMethod; 
    $data['tableName'] = $tableName;    
    
    $defaultFileName = 'consume_' . $tableName . '_' . $targetMethod . '.php';
    $data['outputPath'] = __DIR__ . "/../../$tsClientFilePath/" . $defaultFileName;

    echo "Generando consumidor para método: [{$targetMethod}]...\n";
    $result = $apiClient->create($data);
    var_dump($result);
    echo "---------------------------------------------------\n";
}
