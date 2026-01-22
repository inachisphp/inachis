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

final class TempUploadDirectoryCheck implements CheckInterface
{
    private array $paths = [
        'public/uploads',
        'var/tmp',
    ];

    public function getId(): string { return 'temp_upload_dirs'; }
    public function getLabel(): string { return 'Temporary / Upload Directories'; }
    public function getSection(): string { return 'Security'; }
    public function getSeverity(): string { return 'medium'; }

    public function run(): CheckResult
    {
        $issues = [];
        $info = [];
        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                $info[] = "$path does not exist";
                continue;
            }
            $perms = fileperms($path) & 0777;
            if (($perms & 0x01) || ($perms & 0x02)) { // world-executable / writable
                $issues[] = "$path is world-executable/writable";
            }
        }

        $status = empty($issues) ? 'ok' : (empty($info) ? 'info' : 'warning');
        $value = empty($issues) ? 'Temp / upload directories are secure.' : implode('; ', $issues);

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'Temporary and upload directories are correctly secured.' : 'Some temp/upload directories have insecure permissions.',
            $status === 'ok' ? null : 'Restrict permissions to PHP user; avoid world-executable/writable.',
            $this->getSection(),
            'high'
        );
    }
}