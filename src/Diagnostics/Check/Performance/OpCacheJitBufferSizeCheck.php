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

final class OpCacheJitBufferSizeCheck implements CheckInterface
{
    public function getId(): string { return 'opcache_jit_buffer_size'; }
    public function getLabel(): string { return 'PHP Opcache JIT Buffer Size'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $value = (int) ini_get('opcache.jit_buffer_size');
        $recommended = 134217728; // 128M in bytes
        $status = $value >= $recommended ? 'ok' : 'warning';
        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? "Buffer size is {$value} bytes" : "Buffer size is {$value} bytes (recommended >= 128M)",
            $status === 'ok' ? null : 'Increase opcache.jit_buffer_size to at least 128M for optimal performance.',
            $this->getSection(),
            'high'
        );
    }
}