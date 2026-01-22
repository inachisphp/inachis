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

/**
 * Informational check for IIS webservers.
 * Outputs a detailed checklist and best practice guidance for performance and OpCache.
 */
final class IISModulesCheck implements CheckInterface
{
    public function getId(): string { return 'iis_modules'; }
    public function getLabel(): string { return 'IIS Modules & Performance Guidance'; }
    public function getSection(): string { return 'Webserver'; }

    public function run(): CheckResult
    {
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
        $isIIS = str_contains(strtolower($serverSoftware), 'microsoft-iis');

        if (!$isIIS) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'ok',
                $serverSoftware,
                'Not running on IIS. No IIS-specific guidance required.',
                null,
                $this->getSection(),
                'medium'
            );
        }

        $checklist = [
            'Application Pool Settings' => [
                'Idle Timeout: 0 (never) & Start Mode: Always Running',
                'Recycling: fixed time outside high-use hours',
            ],
            'FastCGI Settings' => [
                'Max Instances: increase based on RAM/CPU cores',
                'Max Requests: increase to reduce process restarts',
                'Idle Timeout: 24 hours to reduce cold starts',
                'Activity Timeout: 90s to allow initial OpCache compilation',
            ],
            'OpCache & PHP Runtime' => [
                'Consider OpCache sharing across FastCGI processes (manual/complex)',
                'Realpath Cache Size: increase to 16M',
                'Realpath Cache TTL: increase to 300s',
                'Programmatic OpCache warmup (optional)',
            ],
            'Filesystem / Antivirus' => [
                'Exclude core application files from antivirus scans to reduce overhead',
            ],
            'Webserver Features' => [
                'Enable HTTP/2',
                'Enable Dynamic/Static Compression (gzip, Brotli)',
                'Configure caching via Cache-Control/Expires headers',
            ],
        ];

        $details = [];
        foreach ($checklist as $category => $items) {
            $details[] = "**$category**";
            foreach ($items as $item) {
                $details[] = "- $item";
            }
            $details[] = '';
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            'info',
            $serverSoftware,
            implode("\n", $details),
            'IIS settings cannot be verified automatically. Follow the checklist above for performance optimization.',
            $this->getSection(),
            'high'
        );
    }
}