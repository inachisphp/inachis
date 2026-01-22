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

final class OpCacheMaxAcceleratedFilesCheck implements CheckInterface
{
    public function getId(): string { return 'opcache_max_accelerated_files'; }
    public function getLabel(): string { return 'PHP Opcache Max Accelerated Files'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $value = (int) ini_get('opcache.max_accelerated_files');
        $recommended = 20000;
        $status = $value >= $recommended ? 'ok' : 'warning';
        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? "Max accelerated files: {$value}" : "Max accelerated files: {$value} (recommended >= 20000)",
            $status === 'ok' ? null : 'Increase opcache.max_accelerated_files to at least 20000.',
            $this->getSection(),
            'high'
        );
    }
}