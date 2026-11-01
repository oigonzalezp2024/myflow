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
        $stmt = $this->pdo->prepare("SELECT id, name FROM categories WHERE name = :name LIMIT 1");
        $paramName = trim($name);
        $stmt->bindParam(':name', $paramName, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new Category($row['name'], (int) $row['id']);
    }

    public function create(Category $category): Category
    {
        $stmt = $this->pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
        $categoryName = $category->getName();
        $stmt->bindParam(':name', $categoryName, PDO::PARAM_STR);
        $stmt->execute();

        return new Category($category->getName(), (int) $this->pdo->lastInsertId());
    }
}
