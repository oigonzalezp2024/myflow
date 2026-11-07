<?php
declare(strict_types=1);

require __DIR__ . '/../clients/ApiClient.php';

// Token con el que este cliente se autentica ante el API Intermediario
$intermediaryToken = 'TU_TOKEN_INTERMEDIARIO_SECRETO';
$endpointIntermediary = 'http://localhost/myflow/public/controllers/consumo.php';
$primaryKey = 'id_proveedor';

// Instanciamos el cliente apuntando al intermediario con su respectivo token de acceso
$apiClient = new ApiClient($endpointIntermediary, $intermediaryToken, $primaryKey);

$methods = ['getAll', 'getById', 'create', 'update', 'delete'];
$tableName = 'pedidos';
$targetServiceEndpoint = 'http://localhost/myflow/public/api/insumos/proveedores/';
$targetServiceTokenVal = 'token_e146b7073904f432bfc121ea3e396708'; // Token que usará el código generado

foreach ($methods as $targetMethod) {
    $data = [];
    $data['host'] = 'localhost';
    $data['db'] = 'test_insumos';
    $data['action'] = 'generate_consumer';
    $data['endpointUrl'] = $targetServiceEndpoint;
    
    // Token que el intermediario inyectará en el archivo PHP resultante
    $data['targetServiceToken'] = $targetServiceTokenVal; 
    
    $data['targetMethod'] = $targetMethod; 
    $data['tableName'] = $tableName;    
    
    $defaultFileName = 'consume_' . $tableName . '_' . $targetMethod . '.php';
    $data['outputPath'] = __DIR__ . '/../controllers/' . $defaultFileName;

    echo "Generando consumidor para método: [{$targetMethod}]...\n";
    $result = $apiClient->create($data);
    var_dump($result);
    echo "---------------------------------------------------\n";
}
