<?php

declare(strict_types=1);

namespace Olimpo\Domain\Interface\Infrastructure\Repositories;

use Olimpo\Domain\Entities\Product;

interface ProductRepositoryInterface
{
    public function create(Product $product): Product;
}
