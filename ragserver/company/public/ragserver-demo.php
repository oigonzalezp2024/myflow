<?php

declare(strict_types=1);

require_once __DIR__ . '/../Clients/RagApiClient.php';

$ragClient = new RagApiClient('http://localhost:9020');

try {
    // 1. Indexar conocimiento en Weaviate a través del servidor Go
    echo "Indexando documentos...\n";
    $addResult = $ragClient->addDocuments([
        ['text' => 'El horario de soporte técnico para mantenimiento industrial es de lunes a viernes de 7:30 AM a 5:30 PM.'],
        ['text' => 'La garantía estándar de los equipos fabricados cubre 12 meses por defectos de soldadura y estructura.'],
    ]);
    print_r($addResult);

    // 2. Consultar al modelo usando el contexto recién guardado
    echo "\nConsultando a Gemini...\n";
    $queryResult = $ragClient->query('¿Cuál es el horario de soporte técnico y cuánto dura la garantía?');
    
    // La respuesta del servidor Go suele venir en la clave 'answer' o 'response'
    print_r($queryResult);

} catch (Throwable $e) {
    echo "Fallo en la operación: " . $e->getMessage() . "\n";
}
