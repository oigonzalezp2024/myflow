<?php

declare(strict_types=1);

namespace Olimpo\Application\UseCases;

use Olimpo\Domain\Interface\Infrastructure\Persistence\TransactionManagerInterface;
use Olimpo\Domain\Interface\Infrastructure\Repositories\CategoryRepositoryInterface;
use Olimpo\Domain\Interface\Infrastructure\Repositories\ProductRepositoryInterface;
use Olimpo\Domain\Entities\Category;
use Olimpo\Domain\Entities\Product;

/**
 *  Start coding belongs to the use case.
 */

class CreateProductUseCase
{
    private TransactionManagerInterface $transactionManager;
    private CategoryRepositoryInterface $categoryRepo;
    private ProductRepositoryInterface $productRepo;

    public function __construct(
        TransactionManagerInterface $transactionManager, 
        CategoryRepositoryInterface $categoryRepo, 
        ProductRepositoryInterface $productRepo
    ) {
        $this->transactionManager = $transactionManager;
        $this->categoryRepo = $categoryRepo;
        $this->productRepo = $productRepo;
    }

    public function ejecutar(string $categoryName, string $productName): array
    {
        return $this->transactionManager->transactional(function () use ($categoryName, $productName) {
            $categorySaved = $this->categoryRepo->findByName($categoryName);

            if ($categorySaved === null) {
                $newCategory = new Category($categoryName);
                $categorySaved = $this->categoryRepo->create($newCategory);
            }

            $product = new Product($categorySaved, $productName);
            $productSaved = $this->productRepo->create($product);

            return [$categorySaved, $productSaved];
        });
    }
}
