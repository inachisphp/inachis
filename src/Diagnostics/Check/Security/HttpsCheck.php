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

final class HttpsCheck implements CheckInterface
{
    public function getId(): string { return 'https'; }
    public function getLabel(): string { return 'HTTPS Enforcement'; }
    public function getSection(): string { return 'Security'; }

    public function run(): CheckResult
    {
        $https = $_SERVER['HTTPS'] ?? '';
        $status = $https === 'on' || $_SERVER['SERVER_PORT'] === 443 ? 'ok' : 'warning';
        $details = $status === 'ok' ? 'HTTPS is enabled.' : 'HTTPS is not detected.';
        $recommendation = $status === 'ok' ? null : 'Enable HTTPS for secure connections.';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            null,
            $details,
            $recommendation,
            $this->getSection(),
            'high'
        );
    }
}
