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

final class SessionCookieLifetimeCheck implements CheckInterface
{
    public function getId(): string { return 'session_cookie_lifetime'; }
    public function getLabel(): string { return 'session.cookie_lifetime'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $value = (int) ini_get('session.cookie_lifetime');

        $status = 'ok';
        if ($value === 0) {
            $status = 'ok';
            $severity = 'low';
        } elseif ($value > 86400) {
            $status = 'warning';
            $severity = 'medium';
        } else {
            $status = 'ok';
            $severity = 'low';
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value . ' seconds',
            $status === 'ok' ? 'Cookie lifetime is acceptable.' : 'Long session cookies can increase hijacking risk.',
            $status !== 'ok' ? 'Consider reducing session.cookie_lifetime to reasonable duration, e.g., 0–86400 seconds.' : null,
            $this->getSection(),
            '',
        );
    }
}