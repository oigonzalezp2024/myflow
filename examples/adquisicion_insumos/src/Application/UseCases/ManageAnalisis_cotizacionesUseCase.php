<?php
namespace Application\UseCases;

use Domain\Repositories\Analisis_cotizacionesRepositoryInterface;
use Application\DTO\Analisis_cotizacionesRequestDTO;

class ManageAnalisis_cotizacionesUseCase {
    private Analisis_cotizacionesRepositoryInterface $repository;

    public function __construct(Analisis_cotizacionesRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function handleList() { return $this->repository->getAll(); }
    public function handleGet($id) { return $this->repository->getById($id); }
    public function handleCreate(Analisis_cotizacionesRequestDTO $dto) { return $this->repository->create($dto->toArray()); }
    public function handleUpdate($id, Analisis_cotizacionesRequestDTO $dto) { return $this->repository->update($id, $dto->toArray()); }
    public function handleDelete($id) { return $this->repository->delete($id); }
}
