<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System;

/**
 * Returns version information about the framework.
 */
final class VersionService
{
    /**
     * @var array<string, string> Current version information
     */
    private array $version;

    /**
     * @param string $versionFile Path to the generated version file
     */
    public function __construct(string $versionFile)
    {
        if (!is_file($versionFile)) {
            $this->version = [
                'version' => 'dev',
                'commit' => 'unknown',
                'build_date' => '',
            ];

            return;
        }

        /** @var array<string, string> $version */
        $version = require $versionFile;

        $this->version = [
            'version' => $version['version'] ?? 'dev',
            'commit' => $version['commit'] ?? 'unknown',
            'build_date' => $version['build_date'] ?? '',
        ];
    }

    /**
     * @return array<string, string> All version information
     */
    public function getAll(): array
    {
        return $this->version;
    }

    /**
     * @return string The version number
     */
    public function getVersion(): string
    {
        return $this->version['version'];
    }

    /**
     * @return string The commit hash
     */
    public function getCommit(): string
    {
        return $this->version['commit'];
    }

    /**
     * @return string The build date
     */
    public function getBuildDate(): string
    {
        return $this->version['build_date'];
    }

    /**
     * Tests if the current framework version satisfies a given constraint.
     */
    public function satisfies(string $constraint): bool
    {
        $version = $this->getVersion();
        $constraint = trim($constraint);

        if ($version === 'dev') {
            return false;
        }

        if (str_starts_with($constraint, '^')) {
            $min = substr($constraint, 1);
            $parts = explode('.', $min);
            $nextMajor = ((int) ($parts[0] ?? 0)) + 1;
            $max = $nextMajor . '.0.0';

            return version_compare($version, $min, '>=')
                && version_compare($version, $max, '<');
        }

        if (str_starts_with($constraint, '~')) {
            $min = substr($constraint, 1);
            $parts = explode('.', $min);
            $nextMinor = ((int) ($parts[1] ?? 0)) + 1;
            $max = ($parts[0] ?? 0) . '.' . $nextMinor . '.0';

            return version_compare($version, $min, '>=')
                && version_compare($version, $max, '<');
        }

        if (preg_match('/^(>=|<=|>|<|!=|=)\s*(.+)$/', $constraint, $matches)) {
            return version_compare($version, $matches[2], $matches[1]);
        }

        return version_compare($version, $constraint, '==');
    }
}
