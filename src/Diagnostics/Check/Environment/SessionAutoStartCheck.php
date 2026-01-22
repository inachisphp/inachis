<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Environment;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class SessionAutoStartCheck implements CheckInterface
{
    public function getId(): string { return 'session_auto_start'; }
    public function getLabel(): string { return 'session.auto_start'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $value = (bool) ini_get('session.auto_start');

        $status = $value ? 'warning' : 'ok';
        $severity = $value ? 'medium' : 'low';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value ? 'enabled' : 'disabled',
            $status === 'ok' ? 'Session auto-start is disabled, as recommended.' : 'session.auto_start is enabled; can interfere with framework session management.',
            $status !== 'ok' ? 'Set session.auto_start=0 in php.ini.' : null,
            $this->getSection(),
            '',
        );
    }
}