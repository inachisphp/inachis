<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics;

final class CheckResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $status, // ok|warning|error|unknown
        public readonly ?string $value,
        public readonly string $details,
        public readonly ?string $recommendation,
        public readonly string $section, // environment|performance|security|webserver|etc
        public readonly string $confidence, // high|medium|low
    ) {}
}
