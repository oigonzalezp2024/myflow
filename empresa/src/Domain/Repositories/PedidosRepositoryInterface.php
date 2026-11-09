<?php
namespace Domain\Repositories;

use Domain\Entities\PedidosEntity;

interface PedidosRepositoryInterface {
    public function getAll(): array;
    public function getById($id): ?PedidosEntity;
    public function create(array $data): int;
    public function update($id, array $data): int;
    public function delete($id): int;
}
