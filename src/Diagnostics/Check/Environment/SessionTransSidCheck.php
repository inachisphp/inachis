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

final class SessionTransSidCheck implements CheckInterface
{
    public function getId(): string { return 'session_use_trans_sid'; }
    public function getLabel(): string { return 'session.use_trans_sid'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $value = (bool) ini_get('session.use_trans_sid');

        $status = $value ? 'error' : 'ok';
        $severity = $value ? 'high' : 'low';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value ? 'enabled' : 'disabled',
            $status === 'ok' ? 'Transparent SID support is disabled.' : 'session.use_trans_sid enabled; exposes session IDs in URLs.',
            $status !== 'ok' ? 'Set session.use_trans_sid=0 in php.ini.' : null,
            $this->getSection(),
            $severity
        );
    }
}