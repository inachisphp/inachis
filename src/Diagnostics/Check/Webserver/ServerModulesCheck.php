<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class ServerModulesCheck implements CheckInterface
{
    public function getId(): string { return 'server_modules'; }
    public function getLabel(): string { return 'Server Modules'; }
    public function getSection(): string { return 'Webserver'; }

    public function run(): CheckResult
    {
        $status = 'ok';
        $details = [];

        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            $checks = [
                'mod_deflate' => 'Gzip compression',
                'mod_brotli' => 'Brotli compression',
                'mod_expires' => 'Expires headers',
            ];
            foreach ($checks as $mod => $desc) {
                if (in_array($mod, $modules)) {
                    $details[] = "$mod: enabled";
                } else {
                    $details[] = "$mod: missing";
                    $status = 'warning';
                }
            }
        } else {
            $details[] = 'Non-Apache HTTPD server or module check unavailable.';
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            null,
            implode("\n", $details),
            $status === 'ok' ? null : 'Verify server modules for performance and compression.',
            $this->getSection(),
            'medium'
        );
    }
}