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

final class SensitiveFilesCheck implements CheckInterface
{
    private array $publicPaths = [
        'public/.env',
        'public/.env.local',
        'public/composer.lock',
        'public/vendor',
    ];

    public function getId(): string { return 'sensitive_files'; }
    public function getLabel(): string { return 'Sensitive Files Exposure'; }
    public function getSection(): string { return 'Security'; }
    public function getSeverity(): string { return 'high'; }

    public function run(): CheckResult
    {
        $issues = [];
        foreach ($this->publicPaths as $path) {
            if (file_exists($path)) {
                $issues[] = $path;
            }
        }

        $status = empty($issues) ? 'ok' : 'warning';
        $value = empty($issues) ? 'No sensitive files exposed.' : implode(', ', $issues);

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'No sensitive files found in public directory.' : 'Sensitive files detected in public directory!',
            $status === 'ok' ? null : 'Move sensitive files out of the webroot.',
            $this->getSection(),
            'high'
        );
    }
}