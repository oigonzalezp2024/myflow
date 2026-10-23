<?php
// API REST Autogenerada con Token para la tabla 'ind_ordenes_fabricacion'
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// Token de acceso requerido en la cabecera Authorization: Bearer <token>
define('API_TOKEN', 'token_5b697e70055224ae57760f7e38ef8217');

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

$pdo = new PDO('mysql:host=localhost;port=3306;dbname=u335689347_indusam;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$id = $_GET['id_orden'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM `ind_ordenes_fabricacion` WHERE `id_orden` = ?');
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch() ?: ['message' => 'Registro no encontrado']);
        } else {
            $stmt = $pdo->query('SELECT * FROM `ind_ordenes_fabricacion`');
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        $sql = 'INSERT INTO `ind_ordenes_fabricacion` (`numero_orden`, `cliente_id`, `asesor_id`, `fabricante_id`, `operario_id`, `producto_id`, `unidades`, `estado`, `fecha_pedido`, `fecha_entrega`, `costo_subtotal`, `costo_mod`, `costo_cif`, `porcentaje_utilidad`, `monto_utilidad`, `monto_total`, `creado_en`, `fecha_actualizacion`) VALUES (:numero_orden, :cliente_id, :asesor_id, :fabricante_id, :operario_id, :producto_id, :unidades, :estado, :fecha_pedido, :fecha_entrega, :costo_subtotal, :costo_mod, :costo_cif, :porcentaje_utilidad, :monto_utilidad, :monto_total, :creado_en, :fecha_actualizacion)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($input);
        echo json_encode(['success' => true, 'id_orden' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_orden']); break; }
        $sql = 'UPDATE `ind_ordenes_fabricacion` SET `numero_orden` = :numero_orden, `cliente_id` = :cliente_id, `asesor_id` = :asesor_id, `fabricante_id` = :fabricante_id, `operario_id` = :operario_id, `producto_id` = :producto_id, `unidades` = :unidades, `estado` = :estado, `fecha_pedido` = :fecha_pedido, `fecha_entrega` = :fecha_entrega, `costo_subtotal` = :costo_subtotal, `costo_mod` = :costo_mod, `costo_cif` = :costo_cif, `porcentaje_utilidad` = :porcentaje_utilidad, `monto_utilidad` = :monto_utilidad, `monto_total` = :monto_total, `creado_en` = :creado_en, `fecha_actualizacion` = :fecha_actualizacion WHERE `id_orden` = :id_orden';
        $input['id_orden'] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($input);
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
        break;

    case 'DELETE':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_orden']); break; }
        $stmt = $pdo->prepare('DELETE FROM `ind_ordenes_fabricacion` WHERE `id_orden` = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
        break;
}
