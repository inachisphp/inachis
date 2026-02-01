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
use Inachis\Util\NumberFormatter;

final class DiskSpaceCheck implements CheckInterface
{
    public function getId(): string { return 'disk_space'; }
    public function getLabel(): string { return 'Disk Free Space'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $free = disk_free_space('/') ?: 0;
        $freeMB = NumberFormatter::formatBytes($free);
        $status = $free < 1024 * 1024 * 2048 ? 'warning' : 'ok'; // less than 2Gb
        $details = "Free disk space: {$freeMB}";

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $freeMB,
            $details,
            $status === 'ok' ? null : 'Disk space is low; consider cleaning logs or temp files.',
            $this->getSection(),
            'high'
        );
    }
}
