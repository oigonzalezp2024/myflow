<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$host = $input['host'] ?? 'localhost';
$port = $input['port'] ?? '3306';
$username = $input['username'] ?? 'root';
$password = $input['password'] ?? '';
$dbName = $input['dbName'] ?? '';
$tableName = $input['tableName'] ?? '';

if (!$dbName || !$tableName) {
    echo json_encode(['success' => false, 'message' => 'Faltan el nombre de la base de datos o la tabla.']);
    exit;
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Consultar información de columnas de la tabla
    $stmt = $pdo->prepare("DESCRIBE `$tableName`");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fields = [];
    foreach ($columns as $col) {
        $fields[] = [
            'Field' => $col['Field'],
            'Type' => $col['Type'],
            'Null' => $col['Null'],
            'Key' => $col['Key'],
            'Default' => $col['Default'],
            'Extra' => $col['Extra']
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Estructura leída correctamente.',
        'fields' => $fields
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión SQL: ' . $e->getMessage()]);
}
