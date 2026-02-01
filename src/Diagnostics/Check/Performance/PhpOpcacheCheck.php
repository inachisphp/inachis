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

final class PhpOpcacheCheck implements CheckInterface
{
    public function getId(): string { return 'php_opcache'; }
    public function getLabel(): string { return 'PHP OPcache'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        if (!function_exists('opcache_get_status')) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'unknown',
                null,
                'OPcache extension is not available in this PHP build.',
                null,
                $this->getSection(),
                'high'
            );
        }

        $status = opcache_get_status(false);

        if ($status['opcache_enabled'] ?? false) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'ok',
                'Enabled',
                'OPcache is active and improving PHP performance.',
                null,
                $this->getSection(),
                'high'
            );
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            'error',
            'Disabled',
            'OPcache is installed but currently disabled.',
            'Enable OPcache in php.ini for better performance.',
            $this->getSection(),
            'high'
        );
    }
}
