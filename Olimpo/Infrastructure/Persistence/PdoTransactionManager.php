<?php

declare(strict_types=1);

namespace Olimpo\Infrastructure\Persistence;

use Olimpo\Domain\Interface\Infrastructure\Persistence\TransactionManagerInterface;
use PDO;

class PdoTransactionManager implements TransactionManagerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function transactional(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
