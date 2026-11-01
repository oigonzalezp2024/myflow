<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use InvalidArgumentException;

class Product
{
    private ?int $id;
    private Category $category;
    private string $name;

    public function __construct(Category $category, string $name, ?int $id = null)
    {
        $this->category = $category;
        $this->setName($name);
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function setName(string $name): void
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $name));

        if ($trimmed === '') {
            throw new InvalidArgumentException("El nombre del producto no puede estar vacío.");
        }

        $length = mb_strlen($trimmed);
        if ($length < 2 || $length > 150) {
            throw new InvalidArgumentException("El nombre del producto debe tener entre 2 y 150 caracteres. (Longitud actual: {$length})");
        }

        if (!preg_match('/^[\p{L}\p{N}\s\-\.]+$/u', $trimmed)) {
            throw new InvalidArgumentException("El nombre del producto contiene caracteres no permitidos.");
        }

        $this->name = $trimmed;
    }
}
