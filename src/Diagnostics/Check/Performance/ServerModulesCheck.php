<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Performance;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class ServerModulesCheck implements CheckInterface
{
    public function getId(): string { return 'server_modules'; }
    public function getLabel(): string { return 'Server Modules / Features'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';
        $serverSoftwareLower = strtolower($serverSoftware);
        $details = [];
        $status = 'ok';

        if (str_contains($serverSoftwareLower, 'apache')) {
            // Apache best-effort module detection
            $modules = [
                'mod_deflate' => 'Gzip compression',
                'mod_brotli'  => 'Brotli compression',
                'mod_expires' => 'Cache control',
            ];

            $loadedModules = function_exists('apache_get_modules') ? apache_get_modules() : [];

            foreach ($modules as $mod => $label) {
                if (empty($loadedModules)) {
                    $details[] = "$label: unknown (PHP cannot inspect modules)";
                    $status = 'warning';
                } else {
                    $enabled = in_array($mod, $loadedModules) ? 'enabled' : 'disabled';
                    $details[] = "$label: $enabled";
                    if ($enabled === 'disabled' && $status !== 'error') {
                        $status = 'warning';
                    }
                }
            }
        } elseif (str_contains($serverSoftwareLower, 'nginx')) {
            // Nginx equivalents via header inspection
            $compression = $this->detectCompression();
            $details[] = "Compression: $compression (gzip/br via headers)";
            $details[] = "Cache/Expires headers: " . ($this->detectExpires() ? 'yes' : 'unknown');
            $status = 'ok';
        } elseif (str_contains($serverSoftwareLower, 'microsoft-iis')) {
            // IIS: cannot detect modules, only headers
            $details[] = "IIS detected. Module detection not available via PHP.";
            $details[] = "Compression: " . $this->detectCompression();
            $details[] = "Cache/Expires headers: " . ($this->detectExpires() ? 'yes' : 'unknown');
            $status = 'ok';
        } else {
            $details[] = "Unknown server software: $serverSoftware";
            $status = 'unknown';
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            null,
            implode("\n", $details),
            "Check if your server modules and features are enabled as recommended.",
            $this->getSection(),
            'medium'
        );
    }

    private function detectCompression(): string
    {
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Encoding:') === 0) {
                return trim(substr($header, 17));
            }
        }
        return 'none';
    }

    private function detectHttp2(): bool
    {
        return isset($_SERVER['SERVER_PROTOCOL']) && str_contains($_SERVER['SERVER_PROTOCOL'], 'HTTP/2');
    }

    private function detectExpires(): bool
    {
        foreach (headers_list() as $header) {
            if (stripos($header, 'Cache-Control:') === 0 || stripos($header, 'Expires:') === 0) {
                return true;
            }
        }
        return false;
    }
}