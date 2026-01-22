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

final class PreloadJitCheck implements CheckInterface
{
    public function getId(): string { return 'preload_jit'; }
    public function getLabel(): string { return 'PHP Opcache JIT Preload'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $value = ini_get('opcache.preload') ?: '(none)';
        $jit = ini_get('opcache.jit') ?: 'n/a';
        $status = (strtolower($jit) === 'tracing') ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'OpCache preload and JIT are configured.' : 'OpCache JIT is not tracing or preload not set.',
            $status === 'ok' ? null : 'Enable opcache.preload if safe and ensure opcache.jit=tracing for best performance.',
            $this->getSection(),
            'high'
        );
    }
}