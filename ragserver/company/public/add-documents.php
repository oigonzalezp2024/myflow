<?php

declare(strict_types=1);

require_once __DIR__ . '/../Clients/RagApiClient.php';

// Configuración de conexión a la base de datos (puedes moverlo a tu .env o contenedor de dependencias)
$dbHost = 'localhost';
$dbName = 'u000000000_indusam';
$dbUser = 'root'; // Cambia por tu usuario
$dbPass = '';     // Cambia por tu contraseña

$ragClient = new RagApiClient('http://localhost:9020');

try {
    // 1. Conexión segura con PDO
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Leyendo materiales de la base de datos...\n";

    // 2. Consultar los registros de la tabla ind_materiales
    // Seleccionamos los campos clave para alimentar el contexto del RAG
    $stmt = $pdo->query("
        SELECT codigo_material, descripcion_material, unidad_medida, precio_unitario_defecto, stock_actual 
        FROM ind_materiales 
    ");
    
    $documents = [];

    while ($row = $stmt->fetch()) {
        // Formatear cada fila como un texto natural legible para el modelo de IA / RAG
        $textDocument = sprintf(
            "Material código %s: %s. Unidad de medida: %s. Precio unitario por defecto: $%s. Stock actual: %s unidades.",
            $row['codigo_material'],
            $row['descripcion_material'],
            $row['unidad_medida'],
            $row['precio_unitario_defecto'],
            $row['stock_actual']
        );

        $documents[] = ['text' => $textDocument];
    }

    if (empty($documents)) {
        echo "No se encontraron registros en la tabla ind_materiales.\n";
        exit;
    }

    // 3. Indexar los documentos generados en Weaviate a través del servidor Go
    echo "Enviando " . count($documents) . " documentos al servidor RAG...\n";
    $addResult = $ragClient->addDocuments($documents);
    print_r($addResult);

} catch (Throwable $e) {
    echo "Fallo en la operación: " . $e->getMessage() . "\n";
}
