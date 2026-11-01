<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Interface\Infrastructure\Repositories\ProductRepositoryInterface;
use App\Domain\Entities\Product;
use LogicException;
use PDO;

class ProductRepository implements ProductRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Product $product): Product
    {
        $category = $product->getCategory();
        if ($category->getId() === null) {
            throw new LogicException("La categoría debe estar persistida antes de asociarle un producto.");
        }

        $stmt = $this->pdo->prepare("INSERT INTO products (category_id, name) VALUES (:category_id, :name)");

        $categoryId = $category->getId();
        $productName = $product->getName();

        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $productName, PDO::PARAM_STR);

        $stmt->execute();

        return new Product($category, $product->getName(), (int) $this->pdo->lastInsertId());
    }
}
