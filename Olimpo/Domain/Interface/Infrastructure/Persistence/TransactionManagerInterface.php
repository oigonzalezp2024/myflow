<?php

declare(strict_types=1);

namespace Olimpo\Domain\Interface\Infrastructure\Persistence;

interface TransactionManagerInterface
{
    public function transactional(callable $callback): mixed;
}
