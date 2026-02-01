<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
