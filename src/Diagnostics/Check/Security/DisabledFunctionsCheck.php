<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Security;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class DisabledFunctionsCheck implements CheckInterface
{
    public function getId(): string { return 'disabled_functions'; }
    public function getLabel(): string { return 'Disabled Functions'; }
    public function getSection(): string { return 'Security'; }

    public function run(): CheckResult
    {
        $disabled = ini_get('disable_functions') ?: '';
        $dangerous = ['exec', 'shell_exec', 'system', 'passthru'];
        $status = 'ok';
        $missing = [];

        foreach ($dangerous as $func) {
            if (!str_contains($disabled, $func)) {
                $status = 'warning';
                $missing[] = $func;
            }
        }

        $details = $missing ? 'Missing disables: ' . implode(', ', $missing) : 'All dangerous functions disabled ✅';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $disabled,
            $details,
            $status === 'ok' ? null : 'Consider disabling dangerous functions for security.',
            $this->getSection(),
            'high'
        );
    }
}