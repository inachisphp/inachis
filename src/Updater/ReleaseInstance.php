<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater;

final readonly class ReleaseInstance
{
    public function __construct(
        public string $identifier,
        public string $version,
        public string $path,
    ) {}
}
