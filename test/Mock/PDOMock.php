<?php

declare(strict_types=1);

namespace App\Test\Mock;

use App\Test\Mock\PDOStatementMock;

use PDO;
use PDOStatement;

class PDOMock extends PDO
{
    private int $autoIncrementId = 10;

    public function __construct()
    {
        // Evita conexión real
    }

    public function beginTransaction(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    public function rollBack(): bool
    {
        return true;
    }

    public function inTransaction(): bool
    {
        return false;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        if (str_contains($query, "SELECT id, name FROM categories")) {
            return new PDOStatementMock([
                ['id' => 11, 'name' => 'Electrónica']
            ]);
        }

        return new PDOStatementMock();
    }

    public function lastInsertId(string|null $name = null): string|false
    {
        return (string) (++$this->autoIncrementId);
    }
}
