<?php

declare(strict_types=1);

namespace App\Domain\Interface\Infrastructure\Repositories;

use App\Domain\Entities\Product;

interface ProductRepositoryInterface
{
    public function create(Product $product): Product;
}
