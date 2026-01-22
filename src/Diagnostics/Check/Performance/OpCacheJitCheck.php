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

final class OpCacheJitCheck implements CheckInterface
{
    public function getId(): string { return 'opcache_jit'; }
    public function getLabel(): string { return 'PHP Opcache JIT'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $jit = ini_get('opcache.jit') ?: 'n/a';
        $status = strtolower($jit) === 'tracing' ? 'ok' : 'warning';
        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $jit,
            $status === 'ok' ? 'Opcache JIT is enabled and set to tracing.' : "Opcache JIT is set to '{$jit}', recommended: tracing.",
            $status === 'ok' ? null : 'Set opcache.jit=tracing in php.ini for best performance.',
            $this->getSection(),
            'high'
        );
    }
}