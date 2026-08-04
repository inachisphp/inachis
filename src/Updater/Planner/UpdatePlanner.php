<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater\Planner;

use Inachis\Exception\Updater\IncompatibleVersionException;
use Inachis\Exception\Updater\NoUpdateAvailableException;
use Inachis\Updater\Release\Manifest;

final readonly class UpdatePlanner
{
    /**
     * Inspects a release Manifest against the current version and plans the update.
     *
     * @throws NoUpdateAvailableException   If target version <= current version
     * @throws IncompatibleVersionException If current version < manifest minimumVersion
     */
    public function plan(string $currentVersion, Manifest $manifest): UpdatePlan
    {
        $normalizedCurrent = $this->normalizeVersion($currentVersion);
        $normalizedTarget = $this->normalizeVersion($manifest->version);
        $normalizedMinimum = $this->normalizeVersion($manifest->minimumVersion);

        // 1. Check if target version is newer than current
        $comparison = version_compare($normalizedTarget, $normalizedCurrent);

        if (0 === $comparison) {
            throw NoUpdateAvailableException::alreadyUpToDate($currentVersion);
        }

        if ($comparison < 0) {
            throw NoUpdateAvailableException::downgradeNotSupported($currentVersion, $manifest->version);
        }

        // 2. Enforce minimum version requirement for step updates
        if (version_compare($normalizedCurrent, $normalizedMinimum, '<')) {
            throw IncompatibleVersionException::minimumVersionNotMet($currentVersion, $manifest->version, $manifest->minimumVersion);
        }

        // 3. Build executable UpdatePlan
        return new UpdatePlan(
            currentVersion: $currentVersion,
            targetVersion: $manifest->version,
            package: $manifest->package,
            archiveUrl: $manifest->archiveUrl,
            replacePaths: $manifest->replace,
            preservePaths: $manifest->preserve,
            migrations: $manifest->migrations,
            requiresMigration: !empty($manifest->migrations),
            type: $manifest->type,
        );
    }

    /**
     * Standardizes version string format (e.g. 'v1.2.0-beta.1' -> '1.2.0-beta.1').
     */
    private function normalizeVersion(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }
}
