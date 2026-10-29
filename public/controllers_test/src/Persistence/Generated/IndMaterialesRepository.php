<?php

declare(strict_types=1);

namespace App\Persistence\Generated;

use PDO;

class IndMaterialesRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?IndMaterialesDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `ind_materiales` WHERE `id_material` = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        return $data ? IndMaterialesDTO::fromArray($data) : null;
    }

    /**
     * @return IndMaterialesDTO[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `ind_materiales`');
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = IndMaterialesDTO::fromArray($row);
        }
        return $results;
    }

    public function create(IndMaterialesDTO $dto): int|string
    {
        $sql = 'INSERT INTO `ind_materiales` (`codigo_material`, `descripcion_material`, `unidad_medida`, `precio_unitario_defecto`, `categoria_id`, `stock_minimo`, `stock_actual`, `stock_maximo`, `creado_en`, `fecha_actualizacion`, `codigo`, `palabras_clave`, `costo`, `sede`, `seccion`, `estante`, `proveedor`, `marca`, `material_nombre`) VALUES (:codigo_material, :descripcion_material, :unidad_medida, :precio_unitario_defecto, :categoria_id, :stock_minimo, :stock_actual, :stock_maximo, :creado_en, :fecha_actualizacion, :codigo, :palabras_clave, :costo, :sede, :seccion, :estante, :proveedor, :marca, :material_nombre)';
        $stmt = $this->pdo->prepare($sql);
        $data = $dto->toArray();
        unset($data['id_material']);

        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function update(IndMaterialesDTO $dto): bool
    {
        $sql = 'UPDATE `ind_materiales` SET `codigo_material` = :codigo_material, `descripcion_material` = :descripcion_material, `unidad_medida` = :unidad_medida, `precio_unitario_defecto` = :precio_unitario_defecto, `categoria_id` = :categoria_id, `stock_minimo` = :stock_minimo, `stock_actual` = :stock_actual, `stock_maximo` = :stock_maximo, `creado_en` = :creado_en, `fecha_actualizacion` = :fecha_actualizacion, `codigo` = :codigo, `palabras_clave` = :palabras_clave, `costo` = :costo, `sede` = :sede, `seccion` = :seccion, `estante` = :estante, `proveedor` = :proveedor, `marca` = :marca, `material_nombre` = :material_nombre WHERE `id_material` = :id_material';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($dto->toArray());
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `ind_materiales` WHERE `id_material` = ?');
        return $stmt->execute([$id]);
    }
}
