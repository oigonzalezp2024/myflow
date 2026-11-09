<?php
namespace Application\DTO;

class PedidosRequestDTO {
    private array $payload;

    public function __construct(array $payload) {
        $this->payload = $payload;
    }

    public function toArray(): array {
        return $this->payload;
    }
}
