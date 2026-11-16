<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Infrastructure\Process\GoServiceProcessManager;

final class GetGoServiceLogsUseCase
{
    public function __construct(
        private GoServiceProcessManager $processManager
    ) {}

    public function execute(int $lines = 50): string
    {
        return $this->processManager->getLogs($lines);
    }
}
