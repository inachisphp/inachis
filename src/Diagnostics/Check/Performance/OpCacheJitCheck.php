<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Performance;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class OpCacheJitCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'opcache_jit';
    }

    public function getLabel(): string
    {
        return 'PHP Opcache JIT';
    }

    public function getSection(): string
    {
        return 'Performance';
    }

    public function run(): CheckResult
    {
        $jit = ini_get('opcache.jit') ?: 'n/a';
        $status = 'tracing' === strtolower($jit) ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $jit,
            'ok' === $status ? 'Opcache JIT is enabled and set to tracing.' : "Opcache JIT is set to '{$jit}', recommended: tracing.",
            'ok' === $status ? null : 'Set opcache.jit=tracing in php.ini for best performance.',
            $this->getSection(),
            'high',
        );
    }
}
