<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Security;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class OpenBasedirCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'open_basedir';
    }

    public function getLabel(): string
    {
        return 'open_basedir Restriction';
    }

    public function getSection(): string
    {
        return 'Security';
    }

    public function run(): CheckResult
    {
        $value = ini_get('open_basedir');
        $status = !empty($value) ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value ?: '(none)',
            'ok' === $status ? 'open_basedir restriction is set.' : 'No open_basedir restriction, PHP can access all paths.',
            'ok' === $status ? null : 'Consider restricting PHP access to only required directories for security.',
            $this->getSection(),
            'medium',
        );
    }
}
