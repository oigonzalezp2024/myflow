<?php
namespace Application\UseCases;

use Domain\Repositories\CotizacionesRepositoryInterface;
use Application\DTO\CotizacionesRequestDTO;

class ManageCotizacionesUseCase {
    private CotizacionesRepositoryInterface $repository;

    public function __construct(CotizacionesRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function handleList() { return $this->repository->getAll(); }
    public function handleGet($id) { return $this->repository->getById($id); }
    public function handleCreate(CotizacionesRequestDTO $dto) { return $this->repository->create($dto->toArray()); }
    public function handleUpdate($id, CotizacionesRequestDTO $dto) { return $this->repository->update($id, $dto->toArray()); }
    public function handleDelete($id) { return $this->repository->delete($id); }
}
