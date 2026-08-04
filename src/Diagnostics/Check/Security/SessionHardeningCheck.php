<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Security;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class SessionHardeningCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'session_hardening';
    }

    public function getLabel(): string
    {
        return 'Session Security';
    }

    public function getSection(): string
    {
        return 'Security';
    }

    public function getSeverity(): string
    {
        return 'high';
    }

    public function run(): CheckResult
    {
        $strict = '1' === ini_get('session.use_strict_mode') ? 'enabled' : 'disabled';
        $cookies = '1' === ini_get('session.use_cookies') ? 'enabled' : 'disabled';
        $onlyCookies = '1' === ini_get('session.use_only_cookies') ? 'enabled' : 'disabled';

        $status = ('enabled' === $strict && 'enabled' === $cookies && 'enabled' === $onlyCookies) ? 'ok' : 'warning';

        $value = "strict=$strict, use_cookies=$cookies, only_cookies=$onlyCookies";

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            'ok' === $status ? 'Session security settings are correct.' : 'Some session security settings are not optimal.',
            'ok' === $status ? null : 'Enable session.use_strict_mode=1, session.use_cookies=1, session.use_only_cookies=1 in php.ini.',
            $this->getSection(),
            'high',
        );
    }
}
