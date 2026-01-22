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

final class MaxExecutionTimeCheck implements CheckInterface
{
    public function getId(): string { return 'max_execution_time'; }
    public function getLabel(): string { return 'Max Execution Time'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $max = (int)(ini_get('max_execution_time') ?: 0);
        $status = $max >= 30 ? 'ok' : 'warning';
        $details = "max_execution_time: $max seconds";

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $max,
            $details,
            $status === 'ok' ? null : 'Increase max_execution_time to at least 30 seconds.',
            $this->getSection(),
            'medium'
        );
    }
}
