<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Infrastructure\Process\GoServiceProcessManager;

final class GetGoServiceStatusUseCase
{
    public function __construct(
        private GoServiceProcessManager $processManager
    ) {}

    public function execute(): array
    {
        return [
            'is_running' => $this->processManager->isRunning(),
            'pid' => $this->processManager->getPid(),
        ];
    }
}
