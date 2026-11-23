<?php
namespace Presentation\Controllers;

require_once __DIR__ . '/../../src/Application/DTO/UsuariosRequestDTO.php';
require_once __DIR__ . '/../../src/Application/UseCases/ManageUsuariosUseCase.php';
require_once __DIR__ . '/../../src/Domain/Entities/UsuariosEntity.php';
require_once __DIR__ . '/../../src/Domain/Repositories/UsuariosRepositoryInterface.php';
require_once __DIR__ . '/../../src/Infrastructure/Persistence/MySQLUsuariosRepository.php';

use Domain\Entities\UsuariosEntity;
use Domain\Repositories\UsuariosRepositoryInterface;
use Infrastructure\Persistence\MySQLUsuariosRepository;
use Application\UseCases\ManageUsuariosUseCase;
use Application\DTO\UsuariosRequestDTO;
use PDO;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

define('API_TOKEN', 'token_6decec139060f5e845dec0646c0cd375');
$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches) || $matches[1] !== API_TOKEN) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

$pdo = new PDO('mysql:host=localhost;port=3308;dbname=testdb;charset=utf8mb4', 'root', '12345678', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$repository = new MySQLUsuariosRepository($pdo);
$useCase = new ManageUsuariosUseCase($repository);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = $_GET['id_usuario'] ?? null;

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
        $dto = new UsuariosRequestDTO($input);
        $insertId = $useCase->handleCreate($dto);
        echo json_encode(['success' => true, 'id_usuario' => $insertId]);
        break;
    case 'PUT':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_usuario']); break; }
        $dto = new UsuariosRequestDTO($input);
        $updated = $useCase->handleUpdate($id, $dto);
        echo json_encode(['success' => true, 'updated' => $updated]);
        break;
    case 'DELETE':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_usuario']); break; }
        $deleted = $useCase->handleDelete($id);
        echo json_encode(['success' => true, 'deleted' => $deleted]);
        break;
}
