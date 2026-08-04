<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Performance;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class RealpathCacheSizeCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'realpath_cache_size';
    }

    public function getLabel(): string
    {
        return 'Realpath Cache Size';
    }

    public function getSection(): string
    {
        return 'Performance';
    }

    public function run(): CheckResult
    {
        $value = (string) ini_get('realpath_cache_size');
        $recommended = 4194304; // 4M in bytes
        $status = $value >= $recommended ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            'ok' === $status ? "Realpath cache size: {$value} bytes" : "Realpath cache size: {$value} bytes (recommended >= 4M)",
            'ok' === $status ? null : 'Increase realpath_cache_size to at least 4M for better PHP file lookup performance.',
            $this->getSection(),
            'high',
        );
    }
}
