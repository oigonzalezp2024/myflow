<?php
/**
 * Cliente PHP cURL autogenerado para consumir la API
 * Endpoint: http://localhost/myflow/public/api/users/UsersApi.php
 */

class ApiClient {
    private $baseUrl;
    private $token;
    private $primaryKey;

    public function __construct($baseUrl = 'http://localhost/myflow/public/api/users/UsersApi.php', $token = 'token_f5f6271b63096ef7737409e644e72cfd', $primaryKey = 'id_user') {
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
        curl_close($ch);
        return json_decode($response, true);
    }

    public function getAll() { return $this->send('GET'); }
    public function getById($id) { return $this->send('GET', null, $id); }
    public function create($data) { return $this->send('POST', $data); }
    public function update($id, $data) { return $this->send('PUT', $data, $id); }
    public function delete($id) { return $this->send('DELETE', null, $id); }
}
