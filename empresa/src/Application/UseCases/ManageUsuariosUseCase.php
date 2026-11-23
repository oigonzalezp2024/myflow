<?php
namespace Application\UseCases;

use Domain\Repositories\UsuariosRepositoryInterface;
use Application\DTO\UsuariosRequestDTO;

class ManageUsuariosUseCase {
    private UsuariosRepositoryInterface $repository;

    public function __construct(UsuariosRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function handleList() { return $this->repository->getAll(); }
    public function handleGet($id) { return $this->repository->getById($id); }
    public function handleCreate(UsuariosRequestDTO $dto) { return $this->repository->create($dto->toArray()); }
    public function handleUpdate($id, UsuariosRequestDTO $dto) { return $this->repository->update($id, $dto->toArray()); }
    public function handleDelete($id) { return $this->repository->delete($id); }
}
