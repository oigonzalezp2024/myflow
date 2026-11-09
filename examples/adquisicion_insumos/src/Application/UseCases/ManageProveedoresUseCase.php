<?php
namespace Application\UseCases;

use Domain\Repositories\ProveedoresRepositoryInterface;
use Application\DTO\ProveedoresRequestDTO;

class ManageProveedoresUseCase {
    private ProveedoresRepositoryInterface $repository;

    public function __construct(ProveedoresRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function handleList() { return $this->repository->getAll(); }
    public function handleGet($id) { return $this->repository->getById($id); }
    public function handleCreate(ProveedoresRequestDTO $dto) { return $this->repository->create($dto->toArray()); }
    public function handleUpdate($id, ProveedoresRequestDTO $dto) { return $this->repository->update($id, $dto->toArray()); }
    public function handleDelete($id) { return $this->repository->delete($id); }
}
