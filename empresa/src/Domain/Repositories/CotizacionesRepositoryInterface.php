<?php
namespace Domain\Repositories;

use Domain\Entities\CotizacionesEntity;

interface CotizacionesRepositoryInterface {
    public function getAll(): array;
    public function getById($id): ?CotizacionesEntity;
    public function create(array $data): int;
    public function update($id, array $data): int;
    public function delete($id): int;
}
