<?php

declare(strict_types=1);

namespace App\Persistence\Generated;

class IndMaterialesDTO
{
    public int $id_material;
    public string $codigo_material;
    public string $descripcion_material;
    public string $unidad_medida;
    public string $precio_unitario_defecto;
    public ?int $categoria_id;
    public float $stock_minimo;
    public float $stock_actual;
    public float $stock_maximo;
    public string $creado_en;
    public ?string $fecha_actualizacion;
    public ?string $codigo;
    public ?string $palabras_clave;
    public ?string $costo;
    public ?string $sede;
    public ?string $seccion;
    public ?string $estante;
    public ?string $proveedor;
    public ?string $marca;
    public ?string $material_nombre;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->id_material = (int)($data['id_material'] ?? 0);
        $dto->codigo_material = ($data['codigo_material'] ?? '');
        $dto->descripcion_material = ($data['descripcion_material'] ?? '');
        $dto->unidad_medida = ($data['unidad_medida'] ?? '');
        $dto->precio_unitario_defecto = ($data['precio_unitario_defecto'] ?? '');
        $dto->categoria_id = isset($data['categoria_id']) ? (int)$data['categoria_id'] : null;
        $dto->stock_minimo = (float)($data['stock_minimo'] ?? 0);
        $dto->stock_actual = (float)($data['stock_actual'] ?? 0);
        $dto->stock_maximo = (float)($data['stock_maximo'] ?? 0);
        $dto->creado_en = ($data['creado_en'] ?? '');
        $dto->fecha_actualizacion = isset($data['fecha_actualizacion']) ? $data['fecha_actualizacion'] : null;
        $dto->codigo = isset($data['codigo']) ? $data['codigo'] : null;
        $dto->palabras_clave = isset($data['palabras_clave']) ? $data['palabras_clave'] : null;
        $dto->costo = isset($data['costo']) ? $data['costo'] : null;
        $dto->sede = isset($data['sede']) ? $data['sede'] : null;
        $dto->seccion = isset($data['seccion']) ? $data['seccion'] : null;
        $dto->estante = isset($data['estante']) ? $data['estante'] : null;
        $dto->proveedor = isset($data['proveedor']) ? $data['proveedor'] : null;
        $dto->marca = isset($data['marca']) ? $data['marca'] : null;
        $dto->material_nombre = isset($data['material_nombre']) ? $data['material_nombre'] : null;
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id_material' => $this->id_material,
            'codigo_material' => $this->codigo_material,
            'descripcion_material' => $this->descripcion_material,
            'unidad_medida' => $this->unidad_medida,
            'precio_unitario_defecto' => $this->precio_unitario_defecto,
            'categoria_id' => $this->categoria_id,
            'stock_minimo' => $this->stock_minimo,
            'stock_actual' => $this->stock_actual,
            'stock_maximo' => $this->stock_maximo,
            'creado_en' => $this->creado_en,
            'fecha_actualizacion' => $this->fecha_actualizacion,
            'codigo' => $this->codigo,
            'palabras_clave' => $this->palabras_clave,
            'costo' => $this->costo,
            'sede' => $this->sede,
            'seccion' => $this->seccion,
            'estante' => $this->estante,
            'proveedor' => $this->proveedor,
            'marca' => $this->marca,
            'material_nombre' => $this->material_nombre,
        ];
    }
}
