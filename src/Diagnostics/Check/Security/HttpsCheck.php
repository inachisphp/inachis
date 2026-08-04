<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Security;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class HttpsCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'https';
    }

    public function getLabel(): string
    {
        return 'HTTPS Enforcement';
    }

    public function getSection(): string
    {
        return 'Security';
    }

    public function run(): CheckResult
    {
        $https = $_SERVER['HTTPS'] ?? '';
        $status = 'on' === $https || 443 === $_SERVER['SERVER_PORT'] ? 'ok' : 'warning';
        $details = 'ok' === $status ? 'HTTPS is enabled.' : 'HTTPS is not detected.';
        $recommendation = 'ok' === $status ? null : 'Enable HTTPS for secure connections.';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            null,
            $details,
            $recommendation,
            $this->getSection(),
            'high',
        );
    }
}
