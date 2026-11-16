<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Infrastructure\Process\GoServiceProcessManager;

final class RestartGoServiceUseCase
{
    public function __construct(
        private GoServiceProcessManager $processManager
    ) {}

    public function execute(): int
    {
        return $this->processManager->restart();
    }
}
