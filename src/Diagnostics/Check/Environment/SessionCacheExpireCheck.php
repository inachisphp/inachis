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

final class SessionCacheExpireCheck implements CheckInterface
{
    public function getId(): string { return 'session_cache_expire'; }
    public function getLabel(): string { return 'session.cache_expire'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $value = (int) ini_get('session.cache_expire'); // in minutes

        if ($value <= 720) {
            $status = 'ok';
            $severity = 'low';
        } else {
            $status = 'warning';
            $severity = 'medium';
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value . ' minutes',
            $status === 'ok' ? 'Session cache expiration is acceptable.' : 'Session cache expiration is high; may cause stale content.',
            $status !== 'ok' ? 'Consider reducing session.cache_expire to <= 720 minutes.' : null,
            $this->getSection(),
            '',
        );
    }
}