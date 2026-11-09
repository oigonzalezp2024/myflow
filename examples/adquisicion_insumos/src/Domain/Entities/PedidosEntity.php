<?php
namespace Domain\Entities;

class PedidosEntity {
    private $field_id_pedido;
    private $field_proveedor_id;

    public function __construct(array $data) {
        $this->field_id_pedido = $data['id_pedido'] ?? null;
        $this->field_proveedor_id = $data['proveedor_id'] ?? null;
    }

    public function toArray(): array {
        return [
            'id_pedido' => $this->field_id_pedido,
            'proveedor_id' => $this->field_proveedor_id,
        ];
    }
}
