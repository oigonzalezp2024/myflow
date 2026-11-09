<?php
namespace Domain\Repositories;

use Domain\Entities\Analisis_cotizacionesEntity;

interface Analisis_cotizacionesRepositoryInterface {
    public function getAll(): array;
    public function getById($id): ?Analisis_cotizacionesEntity;
    public function create(array $data): int;
    public function update($id, array $data): int;
    public function delete($id): int;
}
