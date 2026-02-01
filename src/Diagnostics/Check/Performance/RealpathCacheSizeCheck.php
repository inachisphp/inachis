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

final class RealpathCacheSizeCheck implements CheckInterface
{
    public function getId(): string { return 'realpath_cache_size'; }
    public function getLabel(): string { return 'Realpath Cache Size'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $value = (int) ini_get('realpath_cache_size');
        $recommended = 4194304; // 4M in bytes
        $status = $value >= $recommended ? 'ok' : 'warning';
        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? "Realpath cache size: {$value} bytes" : "Realpath cache size: {$value} bytes (recommended >= 4M)",
            $status === 'ok' ? null : 'Increase realpath_cache_size to at least 4M for better PHP file lookup performance.',
            $this->getSection(),
            'high'
        );
    }
}