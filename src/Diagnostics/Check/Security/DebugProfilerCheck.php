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

final class DebugProfilerCheck implements CheckInterface
{
    public function getId(): string { return 'debug_profiler'; }
    public function getLabel(): string { return 'Debug / Profiler Exposure'; }
    public function getSection(): string { return 'Security'; }
    public function getSeverity(): string { return 'high'; }

    public function run(): CheckResult
    {
        $endpoints = ['_profiler', 'config']; // URLs to test
        $accessible = [];

        foreach ($endpoints as $endpoint) {
            /** @var string */
            $schema = $_SERVER['REQUEST_SCHEME'] ?? 'http';
            /** @var string */
            $host = $_SERVER['HTTP_HOST'];
            $url = $schema . '://' . $host . '/' . $endpoint;
            $headers = @get_headers($url);
            if ($headers && strpos($headers[0], '200') !== false) {
                $accessible[] = $endpoint;
            }
        }

        $status = empty($accessible) ? 'ok' : 'warning';
        $value = empty($accessible) ? 'No debug endpoints accessible.' : implode(', ', $accessible);

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'Debug/profiler endpoints are not accessible.' : 'Debug endpoints accessible!',
            $status === 'ok' ? null : 'Restrict access to _profiler and /config in production.',
            $this->getSection(),
            'high'
        );
    }
}