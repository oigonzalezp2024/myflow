<?php
namespace Infrastructure\Persistence;

use Domain\Repositories\PedidosRepositoryInterface;
use Domain\Entities\PedidosEntity;
use PDO;

class MySQLPedidosRepository implements PedidosRepositoryInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM `pedidos`');
        $rows = $stmt->fetchAll();
        return array_map(fn($row) => new PedidosEntity($row), $rows);
    }

    public function getById($id): ?PedidosEntity {
        $stmt = $this->pdo->prepare('SELECT * FROM `pedidos` WHERE `id_pedido` = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new PedidosEntity($row) : null;
    }

    public function create(array $data): int {
        $sql = 'INSERT INTO `pedidos` (`proveedor_id`) VALUES (:proveedor_id)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, array $data): int {
        $sql = 'UPDATE `pedidos` SET `proveedor_id` = :proveedor_id WHERE `id_pedido` = :id_pedido';
        $data['id_pedido'] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    public function delete($id): int {
        $stmt = $this->pdo->prepare('DELETE FROM `pedidos` WHERE `id_pedido` = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
