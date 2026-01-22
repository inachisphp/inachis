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

final class AllowUrlFopenCheck implements CheckInterface
{
    public function getId(): string { return 'allow_url_fopen_include'; }
    public function getLabel(): string { return 'allow_url_fopen / allow_url_include'; }
    public function getSection(): string { return 'Security'; }
    public function getSeverity(): string { return 'high'; }

    public function run(): CheckResult
    {
        $fopen = ini_get('allow_url_fopen') === '1' ? 'enabled' : 'disabled';
        $include = ini_get('allow_url_include') === '1' ? 'enabled' : 'disabled';
        $status = ($fopen === 'disabled' && $include === 'disabled') ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            "allow_url_fopen=$fopen, allow_url_include=$include",
            $status === 'ok' ? 'Remote file inclusion is disabled.' : 'Remote file inclusion enabled; security risk.',
            $status === 'ok' ? null : 'Set allow_url_fopen=Off and allow_url_include=Off in php.ini for security.',
            $this->getSection(),
            'high'
        );
    }
}