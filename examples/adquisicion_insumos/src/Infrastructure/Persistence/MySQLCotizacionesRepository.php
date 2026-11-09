<?php
namespace Infrastructure\Persistence;

use Domain\Repositories\CotizacionesRepositoryInterface;
use Domain\Entities\CotizacionesEntity;
use PDO;

class MySQLCotizacionesRepository implements CotizacionesRepositoryInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM `cotizaciones`');
        $rows = $stmt->fetchAll();
        return array_map(fn($row) => new CotizacionesEntity($row), $rows);
    }

    public function getById($id): ?CotizacionesEntity {
        $stmt = $this->pdo->prepare('SELECT * FROM `cotizaciones` WHERE `id_cotizacion` = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new CotizacionesEntity($row) : null;
    }

    public function create(array $data): int {
        $sql = 'INSERT INTO `cotizaciones` (`producto`) VALUES (:producto)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, array $data): int {
        $sql = 'UPDATE `cotizaciones` SET `producto` = :producto WHERE `id_cotizacion` = :id_cotizacion';
        $data['id_cotizacion'] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    public function delete($id): int {
        $stmt = $this->pdo->prepare('DELETE FROM `cotizaciones` WHERE `id_cotizacion` = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
