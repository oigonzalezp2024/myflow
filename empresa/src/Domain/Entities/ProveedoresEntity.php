<?php
namespace Domain\Entities;

class ProveedoresEntity {
    private $field_id_proveedor;
    private $field_proveedor_nombre;
    private $field_proveedor_direccion;

    public function __construct(array $data) {
        $this->field_id_proveedor = $data['id_proveedor'] ?? null;
        $this->field_proveedor_nombre = $data['proveedor_nombre'] ?? null;
        $this->field_proveedor_direccion = $data['proveedor_direccion'] ?? null;
    }

    public function toArray(): array {
        return [
            'id_proveedor' => $this->field_id_proveedor,
            'proveedor_nombre' => $this->field_proveedor_nombre,
            'proveedor_direccion' => $this->field_proveedor_direccion,
        ];
    }
}
