<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Updater;

final readonly class ReleaseInstance
{
    public function __construct(
        public string $identifier,
        public string $version,
        public string $path,
    ) {
    }
}
