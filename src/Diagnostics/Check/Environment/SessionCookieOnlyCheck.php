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

final class SessionCookieOnlyCheck implements CheckInterface
{
    public function getId(): string { return 'session_use_only_cookies'; }
    public function getLabel(): string { return 'session.use_only_cookies'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $value = (bool) ini_get('session.use_only_cookies');

        $status = $value ? 'ok' : 'warning';
        $severity = $value ? 'low' : 'high';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value ? 'enabled' : 'disabled',
            $status === 'ok' ? 'Sessions are cookie-only, as recommended.' : 'session.use_only_cookies disabled; risk of session fixation.',
            $status !== 'ok' ? 'Set session.use_only_cookies=1 in php.ini.' : null,
            $this->getSection(),
            '',
        );
    }
}