<?php
namespace Application\DTO;

class Analisis_cotizacionesRequestDTO {
    private array $payload;

    public function __construct(array $payload) {
        $this->payload = $payload;
    }

    public function toArray(): array {
        return $this->payload;
    }
}
