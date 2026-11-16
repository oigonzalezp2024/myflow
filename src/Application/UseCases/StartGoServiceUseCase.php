<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Infrastructure\Process\GoServiceProcessManager;
use RuntimeException;

final class StartGoServiceUseCase
{
    public function __construct(
        private GoServiceProcessManager $processManager
    ) {}

    public function execute(): int
    {
        try {
            return $this->processManager->start();
        } catch (RuntimeException $e) {
            // Aquí puedes registrar el error en un Logger si lo deseas
            throw $e;
        }
    }
}
