<?php
declare(strict_types=1);

/**
 * Cliente PHP cURL autogenerado para consumir el API Gateway / Intermediario
 */
class ApiClientPHP {
    private $baseUrl;
    private $token;
    private $primaryKey;

    public function __construct(
        $baseUrl = 'http://localhost/myflow/public/controllers/consumo.php', 
        $token = 'TU_TOKEN_INTERMEDIARIO_SECRETO', 
        $primaryKey = 'id_user'
    ) {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
        $this->primaryKey = $primaryKey;
    }

    private function send($method, $data = null, $id = null) {
        $url = $this->baseUrl;
        if ($id !== null) {
            $url .= '?' . $this->primaryKey . '=' . $id;
        }

        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token
        ];

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data !== null && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'message' => 'Error cURL: ' . $error];
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    public function getAll() { return $this->send('GET'); }
    public function getById($id) { return $this->send('GET', null, $id); }
    public function create($data) { return $this->send('POST', $data); }
    public function update($id, $data) { return $this->send('PUT', $data, $id); }
    public function delete($id) { return $this->send('DELETE', null, $id); }
}

// Token con el que este cliente se autentica ante el API Intermediario
$intermediaryToken = 'TU_TOKEN_INTERMEDIARIO_SECRETO';
$endpointIntermediary = 'http://localhost/myflow/public/controllers/consumo.php';

// Instanciamos el cliente apuntando al intermediario con su respectivo token de acceso
$apiClient = new ApiClientPHP($endpointIntermediary, $intermediaryToken);

$methods = ['getAll', 'getById', 'create', 'update', 'delete'];
$tableName = 'users';
$targetServiceEndpoint = 'http://localhost/myflow/public/api/users/index.php';
$targetServiceTokenVal = 'token_3075b5bded622b2113aba9daf51d90f0'; // Token que usará el código generado

foreach ($methods as $targetMethod) {
    $data = [];
    $data['action'] = 'generate_consumer';
    $data['endpointUrl'] = $targetServiceEndpoint;
    
    // Token que el intermediario inyectará en el archivo PHP resultante
    $data['targetServiceToken'] = $targetServiceTokenVal; 
    
    $data['targetMethod'] = $targetMethod; 
    $data['tableName'] = $tableName;    
    
    $defaultFileName = 'consume_' . $tableName . '_' . $targetMethod . '.php';
    $data['outputPath'] = __DIR__ . '/' . $defaultFileName;

    echo "Generando consumidor para método: [{$targetMethod}]...\n";
    $result = $apiClient->create($data);
    var_dump($result);
    echo "---------------------------------------------------\n";
}
