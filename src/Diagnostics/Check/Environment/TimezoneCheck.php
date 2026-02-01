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

final class TimezoneCheck implements CheckInterface
{
    public function getId(): string { return 'timezone'; }
    public function getLabel(): string { return 'Default Timezone'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $tz = date_default_timezone_get();
        $valid = in_array($tz, timezone_identifiers_list());
        $status = $tz && $valid ? 'ok' : 'warning';
        $details = $status === 'ok' ? "Timezone set: $tz" : ($valid ? "Invalid timezone $tz" : 'Timezone is not set.');

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $tz,
            $details,
            $status === 'ok' ? null : 'Set a default timezone in php.ini or via date_default_timezone_set().',
            $this->getSection(),
            'high'
        );
    }
}
