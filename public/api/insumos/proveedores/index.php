<?php
// API REST Autogenerada con Token para la tabla 'proveedores'
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// Token de acceso requerido en la cabecera Authorization: Bearer <token>
define('API_TOKEN', 'token_752686ef9eef87ef80aae10fa2b73862');

$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$tokenValido = false;

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    if ($matches[1] === API_TOKEN) {
        $tokenValido = true;
    }
}

if (!$tokenValido) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado. Token inválido o ausente.']);
    exit();
}

$pdo = new PDO('mysql:host=localhost;port=3306;dbname=test_insumos;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$id = $_GET['id_proveedor'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM `proveedores` WHERE `id_proveedor` = ?');
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch() ?: ['message' => 'Registro no encontrado']);
        } else {
            $stmt = $pdo->query('SELECT * FROM `proveedores`');
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        $sql = 'INSERT INTO `proveedores` (`proveedor_nombre`, `vendedor`) VALUES (:proveedor_nombre, :vendedor)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($input);
        echo json_encode(['success' => true, 'id_proveedor' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_proveedor']); break; }
        $sql = 'UPDATE `proveedores` SET `proveedor_nombre` = :proveedor_nombre, `vendedor` = :vendedor WHERE `id_proveedor` = :id_proveedor';
        $input['id_proveedor'] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($input);
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
        break;

    case 'DELETE':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_proveedor']); break; }
        $stmt = $pdo->prepare('DELETE FROM `proveedores` WHERE `id_proveedor` = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
        break;
}
