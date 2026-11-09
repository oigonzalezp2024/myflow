<?php
namespace Domain\Entities;

class CotizacionesEntity {
    private $field_id_cotizacion;
    private $field_producto;

    public function __construct(array $data) {
        $this->field_id_cotizacion = $data['id_cotizacion'] ?? null;
        $this->field_producto = $data['producto'] ?? null;
    }

    public function toArray(): array {
        return [
            'id_cotizacion' => $this->field_id_cotizacion,
            'producto' => $this->field_producto,
        ];
    }
}
