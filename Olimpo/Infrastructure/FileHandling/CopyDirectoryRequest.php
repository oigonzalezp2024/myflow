<?php

declare(strict_types=1);

namespace Olimpo\Infrastructure\FileHandling;

readonly class CopyDirectoryRequest
{
    public function __construct(
        public string $sourceDirectory,
        public string $destinationDirectory,
        public int $permissions = 0755
    ) {}
}
