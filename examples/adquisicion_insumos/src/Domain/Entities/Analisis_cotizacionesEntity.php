<?php
namespace Domain\Entities;

class Analisis_cotizacionesEntity {
    private $field_id_analisis;
    private $field_cotizacion_id;

    public function __construct(array $data) {
        $this->field_id_analisis = $data['id_analisis'] ?? null;
        $this->field_cotizacion_id = $data['cotizacion_id'] ?? null;
    }

    public function toArray(): array {
        return [
            'id_analisis' => $this->field_id_analisis,
            'cotizacion_id' => $this->field_cotizacion_id,
        ];
    }
}
