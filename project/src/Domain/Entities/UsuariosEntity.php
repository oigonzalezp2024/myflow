<?php
namespace Domain\Entities;

class UsuariosEntity {
    private $field_id_usuario;
    private $field_nombre;
    private $field_pass;

    public function __construct(array $data) {
        $this->field_id_usuario = $data['id_usuario'] ?? null;
        $this->field_nombre = $data['nombre'] ?? null;
        $this->field_pass = $data['pass'] ?? null;
    }

    public function toArray(): array {
        return [
            'id_usuario' => $this->field_id_usuario,
            'nombre' => $this->field_nombre,
            'pass' => $this->field_pass,
        ];
    }
}
