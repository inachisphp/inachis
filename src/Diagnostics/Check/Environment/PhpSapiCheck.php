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

final class PhpSapiCheck implements CheckInterface
{
    public function getId(): string { return 'php_sapi'; }
    public function getLabel(): string { return 'PHP SAPI'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $sapi = php_sapi_name();
        $status = in_array($sapi, ['fpm-fcgi', 'apache2handler']) ? 'ok' : 'warning';
        $details = "Detected SAPI: $sapi";

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $sapi,
            $details,
            $status === 'ok' ? null : "Recommended SAPI is FPM or Apache2handler for optimal performance.",
            $this->getSection(),
            'high'
        );
    }
}
