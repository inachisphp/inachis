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

final class SessionHardeningCheck implements CheckInterface
{
    public function getId(): string { return 'session_hardening'; }
    public function getLabel(): string { return 'Session Security'; }
    public function getSection(): string { return 'Security'; }
    public function getSeverity(): string { return 'high'; }

    public function run(): CheckResult
    {
        $strict = ini_get('session.use_strict_mode') === '1' ? 'enabled' : 'disabled';
        $cookies = ini_get('session.use_cookies') === '1' ? 'enabled' : 'disabled';
        $onlyCookies = ini_get('session.use_only_cookies') === '1' ? 'enabled' : 'disabled';

        $status = ($strict === 'enabled' && $cookies === 'enabled' && $onlyCookies === 'enabled') ? 'ok' : 'warning';

        $value = "strict=$strict, use_cookies=$cookies, only_cookies=$onlyCookies";

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'Session security settings are correct.' : 'Some session security settings are not optimal.',
            $status === 'ok' ? null : 'Enable session.use_strict_mode=1, session.use_cookies=1, session.use_only_cookies=1 in php.ini.',
            $this->getSection(),
            'high'
        );
    }
}