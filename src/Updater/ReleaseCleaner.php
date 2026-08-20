<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater;

final class ReleaseCleaner
{
    public function __construct(
        private ReleaseLocator $locator,
    ) {
    }

    /**
     * Clean up old release directories.
     *
     * @param int $keep Number of releases to keep (including current). Minimum is 2 (current + 1 rollback).
     *
     * @return array<string> list of pruned directory paths
     */
    public function prune(int $keep = 3): array
    {
        // Require keeping at least 2 releases (current + 1 rollback)
        $keep = max(2, $keep);

        $releasesDir = $this->locator->releasesDirectory();

        if (!is_dir($releasesDir)) {
            return [];
        }

        // Get currently active release path from symlink
        $currentPath = $this->resolveCurrentPath();

        // Find all valid release directories
        $releases = $this->scanReleases($releasesDir);

        // If total releases are within the threshold, nothing to clean up
        if (count($releases) <= $keep) {
            return [];
        }

        // Sort releases chronologically (newest first)
        // Release folder names follow YYYYMMDDHHIISS-version format
        krsort($releases, SORT_STRING);

        $keptCount = 0;
        $pruned = [];

        foreach ($releases as $timestamp => $path) {
            // ALWAYS keep the active 'current' release, regardless of index
            if (null !== $currentPath && realpath($path) === $currentPath) {
                ++$keptCount;
                continue;
            }

            // Keep top N recent releases (for rollback capability)
            if ($keptCount < $keep) {
                ++$keptCount;
                continue;
            }

            // Safe to remove old release
            $this->removeDirectory($path);
            $pruned[] = $path;
        }

        return $pruned;
    }

    /**
     * Resolves the actual physical path pointed to by /var/www/inachis/current.
     */
    private function resolveCurrentPath(): ?string
    {
        $currentLink = $this->locator->currentLink();

        if (!is_link($currentLink) && !file_exists($currentLink)) {
            return null;
        }

        $realPath = realpath($currentLink);

        return false !== $realPath ? $realPath : null;
    }

    /**
     * Scans releases directory for release folders matching timestamp pattern.
     *
     * @return array<string, string> Keyed by directory name (timestamp prefix) => full path
     */
    private function scanReleases(string $releasesDir): array
    {
        $entries = scandir($releasesDir);
        if (false === $entries) {
            throw new \RuntimeException(sprintf('Unable to read directory "%s".', $releasesDir));
        }

        $releases = [];
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $releasesDir.DIRECTORY_SEPARATOR.$entry;

            // Only inspect directories (ignore stray files)
            if (is_dir($path) && !is_link($path)) {
                $releases[$entry] = $path;
            }
        }

        return $releases;
    }

    /**
     * Recursively delete a release directory safely.
     */
    private function removeDirectory(string $dir): void
    {
        // Extra safeguard: Ensure we are deleting inside the releases folder
        $releasesDir = realpath($this->locator->releasesDirectory());
        $targetDir = realpath($dir);

        if (false === $targetDir || false === $releasesDir || !str_starts_with($targetDir, $releasesDir)) {
            throw new \RuntimeException(sprintf('Refusing to delete target path outside releases directory: "%s"', $dir));
        }

        $files = array_diff(scandir($targetDir) ?: [], ['.', '..']);

        foreach ($files as $file) {
            $filePath = $targetDir.DIRECTORY_SEPARATOR.$file;

            if (is_link($filePath)) {
                // If it's a symlink (e.g. shared storage link), unlink the link only—do NOT follow it!
                unlink($filePath);
            } elseif (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        rmdir($targetDir);
    }
}
