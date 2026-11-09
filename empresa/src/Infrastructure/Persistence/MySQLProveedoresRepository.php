<?php
namespace Infrastructure\Persistence;

use Domain\Repositories\ProveedoresRepositoryInterface;
use Domain\Entities\ProveedoresEntity;
use PDO;

class MySQLProveedoresRepository implements ProveedoresRepositoryInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM `proveedores`');
        $rows = $stmt->fetchAll();
        return array_map(fn($row) => new ProveedoresEntity($row), $rows);
    }

    public function getById($id): ?ProveedoresEntity {
        $stmt = $this->pdo->prepare('SELECT * FROM `proveedores` WHERE `id_proveedor` = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new ProveedoresEntity($row) : null;
    }

    public function create(array $data): int {
        $sql = 'INSERT INTO `proveedores` (`proveedor_nombre`) VALUES (:proveedor_nombre)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, array $data): int {
        $sql = 'UPDATE `proveedores` SET `proveedor_nombre` = :proveedor_nombre WHERE `id_proveedor` = :id_proveedor';
        $data['id_proveedor'] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    public function delete($id): int {
        $stmt = $this->pdo->prepare('DELETE FROM `proveedores` WHERE `id_proveedor` = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
