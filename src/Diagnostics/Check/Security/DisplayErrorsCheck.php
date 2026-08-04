<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Security;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class DisplayErrorsCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'display_errors';
    }

    public function getLabel(): string
    {
        return 'Display Errors';
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
        $value = ini_get('display_errors') ?: '0';
        $status = '0' == $value ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            'ok' === $status ? 'display_errors is off.' : 'display_errors is on!',
            'ok' === $status ? null : 'Set display_errors=0 in php.ini in production.',
            $this->getSection(),
            'high',
        );
    }
}
