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
use Inachis\Util\NumberFormatter;

final class RealpathCacheTtlCheck implements CheckInterface
{
    public function getId(): string { return 'realpath_cache_ttl'; }
    public function getLabel(): string { return 'Realpath Cache TTL'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $value = (int) ini_get('realpath_cache_ttl');
        $recommended = 600;
        $status = $value >= $recommended ? 'ok' : 'warning';

        $humanValue = NumberFormatter::formatSeconds($value);

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? "Realpath cache TTL: {$humanValue}" : "Realpath cache TTL: {$humanValue} (recommended = 600)",
            $status === 'ok' ? null : 'Set realpath_cache_ttl to 600 for optimal PHP path caching.',
            $this->getSection(),
            'high'
        );
    }
}