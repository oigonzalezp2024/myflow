<?php

declare(strict_types=1);

namespace Olimpo\Domain\Interface\Infrastructure\Repositories;

use Olimpo\Domain\Entities\Category;

interface CategoryRepositoryInterface
{
    public function findByName(string $name): ?Category;
    public function create(Category $category): Category;
}
