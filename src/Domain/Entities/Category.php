<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use InvalidArgumentException;

class Category
{
    private ?int $id;
    private string $name;

    public function __construct(string $name, ?int $id = null)
    {
        $this->setName($name);
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function setName(string $name): void
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $name));

        if ($trimmed === '') {
            throw new InvalidArgumentException("El nombre de la categoría no puede estar vacío.");
        }

        $length = mb_strlen($trimmed);
        if ($length < 3 || $length > 100) {
            throw new InvalidArgumentException("El nombre de la categoría debe tener entre 3 y 100 caracteres. (Longitud actual: {$length})");
        }

        if (!preg_match('/^[\p{L}\p{N}\s\-]+$/u', $trimmed)) {
            throw new InvalidArgumentException("El nombre de la categoría contiene caracteres no permitidos.");
        }

        $this->name = $trimmed;
    }
}
