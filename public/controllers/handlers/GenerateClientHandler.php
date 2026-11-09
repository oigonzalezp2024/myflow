<?php
class GenerateClientHandler {
    public static function execute(array $data): void {
        $endpointUrl = $data['endpointUrl'] ?? 'http://localhost/api/MiApi.php';
        $token = $data['token'] ?? 'aqui_tu_token_seguro';
        $primaryKey = $data['primaryKey'] ?? 'id';
        $clientLanguage = $data['clientLanguage'] ?? 'js';

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
            $clientCode .= "class ApiClient {\n";
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
}
