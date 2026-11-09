<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$host = $data['host'] ?? 'localhost';
$port = $data['port'] ?? '3306';
$username = $data['username'] ?? 'root';
$password = $data['password'] ?? '';
$dbName = $data['dbName'] ?? '';
$targetField = $data['field'] ?? ''; // Ej: cotizacion_id o id_proveedor

if (empty($dbName) || empty($targetField)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros dbName o field']);
    exit();
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. Limpiar el nombre del campo para deducir el posible nombre base o buscar directamente
    // Ej: "cotizacion_id" -> "cotizacion", "id_proveedor" -> "proveedor", o buscar coincidencias de PK
    $cleanField = str_replace(['_id', 'id_'], '', $targetField);

    // 2. Buscar en el esquema de la base de datos qué tabla tiene una clave primaria relacionada
    $sqlSchema = "
        SELECT TABLE_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = :dbName 
          AND CONSTRAINT_NAME = 'PRIMARY' 
          AND (COLUMN_NAME = :exactField OR COLUMN_NAME = CONCAT('id_', :cleanField) OR COLUMN_NAME = CONCAT(:cleanField, '_id'))
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sqlSchema);
    $stmt->execute([
        'dbName' => $dbName,
        'exactField' => $targetField,
        'cleanField' => $cleanField
    ]);
    
    $matchedTable = $stmt->fetchColumn();

    // Si no encuentra por coincidencia exacta de PK, intentamos buscar una tabla cuyo nombre coincida con el campo base (ej: tabla 'cotizaciones' para 'cotizacion_id')
    if (!$matchedTable) {
        $possibleTables = [$cleanField, $cleanField . 'es', $cleanField . 's'];
        foreach ($possibleTables as $tName) {
            $checkTable = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
            $checkTable->execute([$dbName, $tName]);
            if ($checkTable->fetchColumn() > 0) {
                $matchedTable = $tName;
                break;
            }
        }
    }

    if (!$matchedTable) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "No se encontró ninguna tabla asociada a la llave: {$targetField}"]);
        exit();
    }

    // 3. Una vez encontrada la tabla, consultamos todos sus registros
    $stmtData = $pdo->query("SELECT * FROM `{$matchedTable}`");
    $rows = $stmtData->fetchAll();

    echo json_encode([
        'success' => true,
        'table' => $matchedTable,
        'data' => $rows
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
