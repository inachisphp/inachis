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

final class SymfonyFilePermissionsCheck implements CheckInterface
{
    private array $paths = [
        'public/imgs',
        'var/cache',
        'var/log',
        'var/sessions',
    ];

    public function getId(): string { return 'file_permissions'; }
    public function getLabel(): string { return 'Symfony File Permissions'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $issues = [];
        foreach ($this->paths as $path) {
            if (!is_dir('/../' . $path)) {
                $issues[] = "$path does not exist.";
                continue;
            }
            if (!is_writable('/../' . $path)) {
                $issues[] = "$path is not writable.";
            }
            $perms = fileperms('/../' . $path) & 0777;
            if (($perms & 0x02) && ($perms & 0x08)) {
                $issues[] = "$path is world-writable.";
            }
        }

        $status = empty($issues) ? 'ok' : 'warning';
        $value = empty($issues) ? 'All key directories writable and secure.' : implode('; ', $issues);

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'All Symfony writable directories are correctly configured.' : 'Some directories have permission issues.',
            $status === 'ok' ? null : 'Ensure var/cache, var/log, var/sessions are writable by PHP, but not world-writable.',
            $this->getSection(),
            'high'
        );
    }
}