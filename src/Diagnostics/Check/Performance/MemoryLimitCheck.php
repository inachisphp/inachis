<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Performance;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class MemoryLimitCheck implements CheckInterface
{
    public function getId(): string { return 'memory_limit'; }
    public function getLabel(): string { return 'Memory Limit'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $limit = ini_get('memory_limit') ?: '0';
        $bytes = $this->parsePhpSize($limit);
        $status = $bytes >= 128 * 1024 * 1024 ? 'ok' : 'warning';
        $details = "memory_limit: $limit ({$bytes} bytes)";

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $limit,
            $details,
            $status === 'ok' ? null : 'Increase memory_limit in php.ini to at least 128M.',
            $this->getSection(),
            'high'
        );
    }

    private function parsePhpSize(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $bytes = (int)$size;

        switch ($unit) {
            case 'g': $bytes *= 1024;
            case 'm': $bytes *= 1024;
            case 'k': $bytes *= 1024;
        }

        return $bytes;
    }
}
