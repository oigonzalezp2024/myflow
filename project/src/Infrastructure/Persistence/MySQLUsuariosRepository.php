<?php
namespace Infrastructure\Persistence;

use Domain\Repositories\UsuariosRepositoryInterface;
use Domain\Entities\UsuariosEntity;
use PDO;

class MySQLUsuariosRepository implements UsuariosRepositoryInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM `usuarios`');
        $rows = $stmt->fetchAll();
        return array_map(fn($row) => new UsuariosEntity($row), $rows);
    }

    public function getById($id): ?UsuariosEntity {
        $stmt = $this->pdo->prepare('SELECT * FROM `usuarios` WHERE `id_usuario` = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new UsuariosEntity($row) : null;
    }

    public function create(array $data): int {
        $sql = 'INSERT INTO `usuarios` (`nombre`, `pass`) VALUES (:nombre, :pass)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, array $data): int {
        $sql = 'UPDATE `usuarios` SET `nombre` = :nombre, `pass` = :pass WHERE `id_usuario` = :id_usuario';
        $data['id_usuario'] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    public function delete($id): int {
        $stmt = $this->pdo->prepare('DELETE FROM `usuarios` WHERE `id_usuario` = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
