<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Package;

trait ManifestHelpers
{
    /**
     * Returns a string value from a manifest.
     *
     * @param array<string,mixed> $manifest
     */
    protected function getString(
        array $manifest,
        string $key,
        string $default = '',
    ): string {
        return isset($manifest[$key]) && is_string($manifest[$key])
            ? $manifest[$key]
            : $default;
    }


    /**
     * Extracts a feature list from a manifest section.
     *
     * Example:
     *
     * requires:
     *   features:
     *     - gallery
     *
     * @param array<string,mixed> $manifest
     *
     * @return list<string>
     */
    protected function extractFeatures(
        array $manifest,
        string $section,
    ): array {
        $sectionData = $manifest[$section] ?? null;

        if (!is_array($sectionData)) {
            return [];
        }

        $features = $sectionData['features'] ?? null;

        if (!is_array($features)) {
            return [];
        }

        return array_values(
            array_filter(
                $features,
                static fn (mixed $feature): bool => is_string($feature)
            )
        );
    }


    /**
     * Returns a manifest string if it exists.
     *
     * Unlike getString(), this returns null for missing values.
     *
     * Useful for optional metadata.
     *
     * @param array<string,mixed> $manifest
     */
    protected function getNullableString(
        array $manifest,
        string $key,
    ): ?string {
        return isset($manifest[$key]) && is_string($manifest[$key])
            ? $manifest[$key]
            : null;
    }

    /**
     * Finds the first recognised screenshot file.
     */
    protected function findScreenshot(
        string $directory,
    ): ?string {
        foreach ([
            'screenshot.webp',
            'screenshot.png',
            'screenshot.jpg',
            'screenshot.jpeg',
        ] as $filename) {

            $path = $directory . DIRECTORY_SEPARATOR . $filename;

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
