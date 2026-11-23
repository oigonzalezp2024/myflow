<?php
namespace Domain\Repositories;

use Domain\Entities\UsuariosEntity;

interface UsuariosRepositoryInterface {
    public function getAll(): array;
    public function getById($id): ?UsuariosEntity;
    public function create(array $data): int;
    public function update($id, array $data): int;
    public function delete($id): int;
}
