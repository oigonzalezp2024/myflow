<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Interface\Infrastructure\Repositories\CategoryRepositoryInterface;
use App\Domain\Entities\Category;
use PDO;

class CategoryRepository implements CategoryRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByName(string $name): ?Category
    {
        $stmt = $this->pdo->prepare("SELECT id_categoria, categoria_nombre FROM categorias_producto WHERE categoria_nombre = :categoria_nombre LIMIT 1");
        $paramName = trim($name);
        $stmt->bindParam(':categoria_nombre', $paramName, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new Category($row['categoria_nombre'], (int) $row['id_categoria']);
    }

    public function create(Category $category): Category
    {
        $stmt = $this->pdo->prepare("INSERT INTO categorias_producto (categoria_nombre) VALUES (:categoria_nombre)");
        $categoryName = $category->getName();
        $stmt->bindParam(':categoria_nombre', $categoryName, PDO::PARAM_STR);
        $stmt->execute();

        return new Category($category->getName(), (int) $this->pdo->lastInsertId());
    }
}
