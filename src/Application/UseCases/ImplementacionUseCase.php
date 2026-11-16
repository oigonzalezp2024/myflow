<?php

// Instanciamos el manager para un servicio específico (ej. el RAG Server pppp)
$processManager = new GoServiceProcessManager(
    'ragserver-pppp', 
    '/ruta/a/tu/proyecto/go', 
    '/ruta/a/storage/logs'
);

// Arrancar el servicio mediante su caso de uso
$startUseCase = new StartGoServiceUseCase($processManager);
$pid = $startUseCase->execute();

// Consultar estado
$statusUseCase = new GetGoServiceStatusUseCase($processManager);
$status = $statusUseCase->execute(); 
// Retorna: ['is_running' => true, 'pid' => 12345]

```