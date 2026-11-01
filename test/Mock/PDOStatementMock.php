<?php

declare(strict_types=1);

namespace App\Test\Mock;

use PDO;
use PDOStatement;

class PDOStatementMock extends PDOStatement
{
    private array $fetchData;
    private int $fetchIndex = 0;
    private array $boundValues = [];

    public function __construct(array $fetchData = [])
    {
        $this->fetchData = $fetchData;
    }

    public function bindParam($param, &$variable, $type = PDO::PARAM_STR, $len = null, $driverOptions = null): bool
    {
        $this->boundValues[$param] = $variable;
        return true;
    }

    public function execute($inputParameters = null): bool
    {
        return true;
    }

    public function fetch($mode = PDO::FETCH_DEFAULT, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0): mixed
    {
        if (isset($this->fetchData[$this->fetchIndex])) {
            return $this->fetchData[$this->fetchIndex++];
        }
        return false;
    }
}
