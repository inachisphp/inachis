<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
        $raw = ini_get('expose_php');
        $value = ($raw === false) ? '' : (string) $raw;
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