<?php

declare(strict_types=1);

namespace Olimpo\Infrastructure\Repositories;

use Olimpo\Domain\Interface\Infrastructure\Repositories\ProductRepositoryInterface;
use Olimpo\Domain\Entities\Product;
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

        $stmt = $this->pdo->prepare("INSERT INTO productos (categoria_id, producto_nombre) VALUES (:categoria_id, :producto_nombre)");

        $categoryId = $category->getId();
        $productName = $product->getName();

        $stmt->bindParam(':categoria_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':producto_nombre', $productName, PDO::PARAM_STR);

        $stmt->execute();

        return new Product($category, $product->getName(), (int) $this->pdo->lastInsertId());
    }
}
