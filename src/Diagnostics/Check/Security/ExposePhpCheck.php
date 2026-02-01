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

final class ExposePhpCheck implements CheckInterface
{
    public function getId(): string { return 'expose_php'; }
    public function getLabel(): string { return 'PHP Expose Version'; }
    public function getSection(): string { return 'Security'; }
    public function getSeverity(): string { return 'medium'; }

    public function run(): CheckResult
    {
        $value = ini_get('expose_php');
        $status = ($value === '0' || strtolower($value) === 'off') ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'PHP version exposure is disabled.' : 'PHP exposes its version in headers, which is a security risk.',
            $status === 'ok' ? null : 'Set expose_php=Off in php.ini.',
            $this->getSection(),
            'medium'
        );
    }
}