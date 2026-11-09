<?php
namespace Domain\Repositories;

use Domain\Entities\ProveedoresEntity;

interface ProveedoresRepositoryInterface {
    public function getAll(): array;
    public function getById($id): ?ProveedoresEntity;
    public function create(array $data): int;
    public function update($id, array $data): int;
    public function delete($id): int;
}
