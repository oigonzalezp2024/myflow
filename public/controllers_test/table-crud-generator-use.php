<?php

declare(strict_types=1);

require_once __DIR__ . '/../controllers/TableCrudGenerator.php';

use App\Generators\TableCrudGenerator;

$dbConfig = [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'test_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

$outputPath = __DIR__ . '/src/Persistence/Generated';

$generator = new TableCrudGenerator($dbConfig, $outputPath);

// Ejecución especificando la tabla 'ind_materiales'
$files = $generator->generate('ind_materiales', 'App\\Persistence\\Generated');

print_r($files);
