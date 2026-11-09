<?php
namespace Presentation\Controllers;

require_once __DIR__ . '/../../src/Application/DTO/Analisis_cotizacionesRequestDTO.php';
require_once __DIR__ . '/../../src/Application/UseCases/ManageAnalisis_cotizacionesUseCase.php';
require_once __DIR__ . '/../../src/Domain/Entities/Analisis_cotizacionesEntity.php';
require_once __DIR__ . '/../../src/Domain/Repositories/Analisis_cotizacionesRepositoryInterface.php';
require_once __DIR__ . '/../../src/Infrastructure/Persistence/MySQLAnalisis_cotizacionesRepository.php';

use Domain\Entities\Analisis_cotizacionesEntity;
use Domain\Repositories\Analisis_cotizacionesRepositoryInterface;
use Infrastructure\Persistence\MySQLAnalisis_cotizacionesRepository;
use Application\UseCases\ManageAnalisis_cotizacionesUseCase;
use Application\DTO\Analisis_cotizacionesRequestDTO;
use PDO;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

define('API_TOKEN', 'token_1a8e975cd70ffbeeba8876fbb0e050de');
$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches) || $matches[1] !== API_TOKEN) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

$pdo = new PDO('mysql:host=localhost;port=3306;dbname=empresa;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$repository = new MySQLAnalisis_cotizacionesRepository($pdo);
$useCase = new ManageAnalisis_cotizacionesUseCase($repository);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = $_GET['id_analisis'] ?? null;

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
        $dto = new Analisis_cotizacionesRequestDTO($input);
        $insertId = $useCase->handleCreate($dto);
        echo json_encode(['success' => true, 'id_analisis' => $insertId]);
        break;
    case 'PUT':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_analisis']); break; }
        $dto = new Analisis_cotizacionesRequestDTO($input);
        $updated = $useCase->handleUpdate($id, $dto);
        echo json_encode(['success' => true, 'updated' => $updated]);
        break;
    case 'DELETE':
        if (!$id) { echo json_encode(['error' => 'Se requiere el campo id_analisis']); break; }
        $deleted = $useCase->handleDelete($id);
        echo json_encode(['success' => true, 'deleted' => $deleted]);
        break;
}
