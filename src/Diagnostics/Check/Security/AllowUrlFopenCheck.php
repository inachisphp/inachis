<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Security;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class AllowUrlFopenCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'allow_url_fopen_include';
    }

    public function getLabel(): string
    {
        return 'allow_url_fopen / allow_url_include';
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
        $fopen = '1' === ini_get('allow_url_fopen') ? 'enabled' : 'disabled';
        $include = '1' === ini_get('allow_url_include') ? 'enabled' : 'disabled';
        $status = ('disabled' === $fopen && 'disabled' === $include) ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            "allow_url_fopen=$fopen, allow_url_include=$include",
            'ok' === $status ? 'Remote file inclusion is disabled.' : 'Remote file inclusion enabled; security risk.',
            'ok' === $status ? null : 'Set allow_url_fopen=Off and allow_url_include=Off in php.ini for security.',
            $this->getSection(),
            'high',
        );
    }
}
