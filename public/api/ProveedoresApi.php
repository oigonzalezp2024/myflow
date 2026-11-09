<?php
namespace Presentation\Controllers;

require_once __DIR__ . '/../../src/Application/DTO/ProveedoresRequestDTO.php';
require_once __DIR__ . '/../../src/Application/UseCases/ManageProveedoresUseCase.php';
require_once __DIR__ . '/../../src/Domain/Entities/ProveedoresEntity.php';
require_once __DIR__ . '/../../src/Domain/Repositories/ProveedoresRepositoryInterface.php';
require_once __DIR__ . '/../../src/Infrastructure/Persistence/MySQLProveedoresRepository.php';

use Domain\Entities\ProveedoresEntity;
use Domain\Repositories\ProveedoresRepositoryInterface;
use Infrastructure\Persistence\MySQLProveedoresRepository;
use Application\UseCases\ManageProveedoresUseCase;
use Application\DTO\ProveedoresRequestDTO;
use PDO;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

define('API_TOKEN', 'token_025935d132efc02a2c50af3982720378');
$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches) || $matches[1] !== API_TOKEN) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

$pdo = new PDO('mysql:host=localhost;port=3306;dbname=adquisicion_insumos;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$repository = new MySQLProveedoresRepository($pdo);
$useCase = new ManageProveedoresUseCase($repository);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = $_GET['id_proveedor'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            $entity = $useCase->handleGet($id);
            echo json_encode($entity ? $entity->toArray() : ['message' => 'Registro no encontrado']);
        } else {
            $entities = $useCase->handleList();
            echo json_encode(array_map(fn($e) => $e->toArray(), $entities));
        }
        break;
    case 'POST':
        $dto = new ProveedoresRequestDTO($input);
        $insertId = $useCase->handleCreate($dto);
        echo json_encode(['success' => true, 'id_proveedor' => $insertId]);
        break;
    case 'PUT':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_proveedor']); break; }
        $dto = new ProveedoresRequestDTO($input);
        $updated = $useCase->handleUpdate($id, $dto);
        echo json_encode(['success' => true, 'updated' => $updated]);
        break;
    case 'DELETE':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_proveedor']); break; }
        $deleted = $useCase->handleDelete($id);
        echo json_encode(['success' => true, 'deleted' => $deleted]);
        break;
}
