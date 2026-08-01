<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Updater;

use Inachis\Service\System\Maintenance\MaintenanceManager;
use RuntimeException;
use Throwable;

final class ReleaseRollback
{
    public function __construct(
        private ReleaseLocator $locator,
        private SymlinkManager $symlinkManager,
        private MaintenanceManager $maintenanceManager,
    ) {}

    /**
     * Inspects releases/ directory and returns available rollback targets.
     *
     * @return list<ReleaseInstance> Sorted newest first (index 0 is previous release)
     */
    public function availableRollbacks(): array
    {
        $releasesDir = $this->locator->releasesDirectory();
        if (!is_dir($releasesDir)) {
            return [];
        }

        $currentPath = $this->resolveCurrentPath();
        $entries = scandir($releasesDir);
        if ($entries === false) {
            return [];
        }

        $releases = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $releasesDir . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path) && !is_link($path)) {
                // Extract version string from YYYYMMDDHHIISS-version directory name format
                $version = str_contains($entry, '-')
                    ? substr($entry, strpos($entry, '-') + 1)
                    : $entry;

                // Do not include active release in rollback targets
                if ($currentPath !== null && realpath($path) === $currentPath) {
                    continue;
                }

                $releases[$entry] = new ReleaseInstance(
                    identifier: $entry,
                    version: $version,
                    path: $path
                );
            }
        }

        // Sort chronologically (newest first)
        krsort($releases, SORT_STRING);

        return array_values($releases);
    }

    /**
     * Atomically rolls back to the specified ReleaseInstance or the previous release.
     */
    public function rollback(?ReleaseInstance $targetRelease = null): ReleaseInstance
    {
        if ($targetRelease === null) {
            $candidates = $this->availableRollbacks();
            if (empty($candidates)) {
                throw new RuntimeException('No previous release available to roll back to.');
            }
            $targetRelease = $candidates[0]; // Most recent previous release
        }

        if (!is_dir($targetRelease->path)) {
            throw new RuntimeException(
                sprintf('Rollback target path "%s" does not exist.', $targetRelease->path)
            );
        }

        $this->maintenanceManager->enable();

        try {
            // 1. Atomically switch /current symlink to target release path
            $this->symlinkManager->switchCurrent(
                $this->locator->currentLink(),
                $targetRelease->path
            );

            // 2. Reset OPCache if enabled
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        } catch (Throwable $exception) {
            $this->maintenanceManager->disable();
            throw new RuntimeException(
                sprintf('Rollback failed: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        $this->maintenanceManager->disable();

        return $targetRelease;
    }

    private function resolveCurrentPath(): ?string
    {
        $currentLink = $this->locator->currentLink();
        if (!is_link($currentLink) && !file_exists($currentLink)) {
            return null;
        }

        $realPath = realpath($currentLink);

        return $realPath !== false ? $realPath : null;
    }
}
