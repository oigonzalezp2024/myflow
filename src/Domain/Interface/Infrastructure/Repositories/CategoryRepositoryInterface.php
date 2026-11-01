<?php

declare(strict_types=1);

namespace App\Domain\Interface\Infrastructure\Repositories;

use App\Domain\Entities\Category;

interface CategoryRepositoryInterface
{
    public function findByName(string $name): ?Category;
    public function create(Category $category): Category;
}
