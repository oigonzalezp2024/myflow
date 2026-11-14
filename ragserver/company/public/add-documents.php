<?php

declare(strict_types=1);

// Evitar que el script se detenga por tiempo de ejecución debido a las pausas
set_time_limit(0);

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
    $pdo = new PDO($dsn, $dbHost === 'localhost' ? $dbUser : $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Leyendo materiales de la base de datos...\n";

    // 2. Consultar los registros de la tabla ind_materiales (hasta 100 en total)
    $stmt = $pdo->query("
        SELECT codigo_material, descripcion_material, unidad_medida, precio_unitario_defecto, stock_actual 
        FROM ind_materiales ORDER BY id_material DESC LIMIT 10
    ");
    
    $batchSize = 10; // Cambiado a 10 registros por lote
    $documents = [];
    $totalProcessed = 0;
    $batchNumber = 1;

    // Recoger todos los registros para poder contar y manejar los lotes y las pausas
    $rows = $stmt->fetchAll();
    $totalRows = count($rows);

    foreach ($rows as $index => $row) {
        // Formatear cada fila como texto natural legible para el RAG
        $textDocument = sprintf(
            "Material código %s: %s. Unidad de medida: %s. Precio unitario por defecto: $%s. Stock actual: %s unidades.",
            $row['codigo_material'],
            $row['descripcion_material'],
            $row['unidad_medida'],
            $row['precio_unitario_defecto'],
            $row['stock_actual']
        );

        $documents[] = ['text' => $textDocument];

        // 3. Cuando el lote alcanza los 10 registros, se envía
        $esUltimoRegistro = ($index === $totalRows - 1);
        
        if (count($documents) === $batchSize || $esUltimoRegistro) {
            echo "Enviando lote #{$batchNumber} (" . count($documents) . " documentos)...\n";
            $addResult = $ragClient->addDocuments($documents);
            print_r($addResult);

            $totalProcessed += count($documents);
            
            // Liberar memoria explícitamente
            unset($documents);
            $documents = []; 

            // 4. Pausa de 1 minuto (60 segundos) si no es el último lote procesado
            if (!$esUltimoRegistro) {
                echo "Pausa de 1 minuto para respetar límites...\n";
                sleep(60);
            }

            $batchNumber++;
        }
    }

    if ($totalProcessed === 0) {
        echo "No se encontraron registros en la tabla ind_materiales.\n";
    } else {
        echo "\nProceso finalizado. Total de documentos indexados: {$totalProcessed}\n";
    }

} catch (Throwable $e) {
    echo "Fallo en la operación: " . $e->getMessage() . "\n";
}
