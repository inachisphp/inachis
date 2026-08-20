<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Performance;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class PreloadJitCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'preload_jit';
    }

    public function getLabel(): string
    {
        return 'PHP Opcache JIT Preload';
    }

    public function getSection(): string
    {
        return 'Performance';
    }

    public function run(): CheckResult
    {
        $value = ini_get('opcache.preload') ?: '(none)';
        $jit = ini_get('opcache.jit') ?: 'n/a';
        $status = ('tracing' === strtolower($jit)) ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            'ok' === $status ? 'OpCache preload and JIT are configured.' : 'OpCache JIT is not tracing or preload not set.',
            'ok' === $status ? null : 'Enable opcache.preload if safe and ensure opcache.jit=tracing for best performance.',
            $this->getSection(),
            'high',
        );
    }
}
