<?php
namespace Infrastructure\Persistence;

use Domain\Repositories\Analisis_cotizacionesRepositoryInterface;
use Domain\Entities\Analisis_cotizacionesEntity;
use PDO;

class MySQLAnalisis_cotizacionesRepository implements Analisis_cotizacionesRepositoryInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM `analisis_cotizaciones`');
        $rows = $stmt->fetchAll();
        return array_map(fn($row) => new Analisis_cotizacionesEntity($row), $rows);
    }

    public function getById($id): ?Analisis_cotizacionesEntity {
        $stmt = $this->pdo->prepare('SELECT * FROM `analisis_cotizaciones` WHERE `id_analisis` = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Analisis_cotizacionesEntity($row) : null;
    }

    public function create(array $data): int {
        $sql = 'INSERT INTO `analisis_cotizaciones` (`cotizacion_id`) VALUES (:cotizacion_id)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, array $data): int {
        $sql = 'UPDATE `analisis_cotizaciones` SET `cotizacion_id` = :cotizacion_id WHERE `id_analisis` = :id_analisis';
        $data['id_analisis'] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    public function delete($id): int {
        $stmt = $this->pdo->prepare('DELETE FROM `analisis_cotizaciones` WHERE `id_analisis` = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
