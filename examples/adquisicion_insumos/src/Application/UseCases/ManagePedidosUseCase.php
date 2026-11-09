<?php
namespace Application\UseCases;

use Domain\Repositories\PedidosRepositoryInterface;
use Application\DTO\PedidosRequestDTO;

class ManagePedidosUseCase {
    private PedidosRepositoryInterface $repository;

    public function __construct(PedidosRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function handleList() { return $this->repository->getAll(); }
    public function handleGet($id) { return $this->repository->getById($id); }
    public function handleCreate(PedidosRequestDTO $dto) { return $this->repository->create($dto->toArray()); }
    public function handleUpdate($id, PedidosRequestDTO $dto) { return $this->repository->update($id, $dto->toArray()); }
    public function handleDelete($id) { return $this->repository->delete($id); }
}
