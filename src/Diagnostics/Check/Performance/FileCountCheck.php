<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Performance;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class FileCountCheck implements CheckInterface
{
    /** @var list<string> */
    private array $paths = [
        'src',
        'templates',
    ];

    public function getId(): string
    {
        return 'file_count';
    }

    public function getLabel(): string
    {
        return 'File Count / Path Thrashing';
    }

    public function getSection(): string
    {
        return 'Performance';
    }

    public function run(): CheckResult
    {
        $totalFiles = 0;
        foreach ($this->paths as $path) {
            if (is_dir($path)) {
                $totalFiles += $this->countFiles($path);
            }
        }

        $threshold = 20000; // rough guideline for OpCache max accelerated files
        $status = $totalFiles <= $threshold ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            (string) $totalFiles,
            'ok' === $status ? "Total files: $totalFiles" : "Total files: $totalFiles (may stress OpCache / realpath cache)",
            'ok' === $status ? null : 'Consider increasing opcache.max_accelerated_files or realpath_cache_size.',
            $this->getSection(),
            'high',
        );
    }

    /**
     * Counts the number of fiels in the folder.
     */
    private function countFiles(string $dir): int
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $count = 0;
        foreach ($rii as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isFile()) {
                ++$count;
            }
        }

        return $count;
    }
}
