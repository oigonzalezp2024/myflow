<?php

declare(strict_types=1);

final class RagApiClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $baseUrl = 'http://localhost:9020', int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Envía uno o varios documentos de texto para ser vectorizados y almacenados.
     *
     * @param array<int, array{text: string}> $documents Ejemplo: [['text' => 'Contenido del documento...']]
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    public function addDocuments(array $documents): array
    {
        return $this->post('/add/', [
            'documents' => $documents,
        ]);
    }

    /**
     * Realiza una consulta en lenguaje natural contra los documentos indexados.
     *
     * @param string $question
     * @return string|array<string, mixed>
     * @throws RuntimeException
     */
    public function query(string $question): mixed
    {
        return $this->post('/query/', [
            'content' => $question,
        ], false); // Indicamos que espere texto/respuesta libre
    }

    /**
     * Ejecuta la petición POST con payload JSON.
     *
     * @param string $endpoint
     * @param array<string, mixed> $payload
     * @param bool $expectJson
     * @return mixed
     * @throws RuntimeException
     */
    private function post(string $endpoint, array $payload, bool $expectJson = true): mixed
    {
        $url = $this->baseUrl . $endpoint;
        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('No se pudo inicializar la sesión cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($jsonPayload),
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Error de conexión cURL hacia {$url}: {$curlError}");
        }

        if ($httpCode >= 400) {
            throw new RuntimeException("Error HTTP {$httpCode} desde el servidor RAG: {$response}");
        }

        if (empty($response)) {
            return ['status' => 'ok', 'http_code' => $httpCode];
        }

        // Si se espera JSON (como en /add/), lo decodificamos
        if ($expectJson) {
            return json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
        }

        // Si no estrictamente se espera JSON (como en /query/), intentamos decodificarlo 
        // si viene en formato JSON, de lo contrario retornamos el texto plano tal cual.
        $decoded = json_decode((string) $response, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $response;
    }
}
