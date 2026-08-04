<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater\Release;

final class ManifestFactory
{
    public function create(array $data): Manifest
    {
        // Support aliases between build process keys and Manifest keys
        $version = $data['version'] ?? null;
        $package = $data['package'] ?? $data['archive'] ?? null;
        $sha256 = $data['packageSha256'] ?? $data['sha256'] ?? null;
        $minimum = $data['minimumVersion'] ?? '0.0.0';

        if (null === $version || null === $package || null === $sha256) {
            throw new \RuntimeException('Release manifest is missing required version, package/archive, or sha256 properties.');
        }

        return new Manifest(
            version: (string) $version,
            minimumVersion: (string) $minimum,
            package: (string) $package,
            packageSha256: (string) $sha256,
            migrations: array_values($data['migrations'] ?? []),
            preserve: array_values($data['preserve'] ?? []),
            replace: array_values($data['replace'] ?? []),
            type: (string) ($data['type'] ?? 'core'),
            releaseNotes: isset($data['releaseNotes']) ? (string) $data['releaseNotes'] : null,
            publishedAt: isset($data['published']) ? (string) $data['published'] : null,
        );
    }

    public function fromJson(string $json): Manifest
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Invalid release manifest JSON.', previous: $exception);
        }

        if (!is_array($data)) {
            throw new \RuntimeException('Release manifest must contain a JSON object.');
        }

        return $this->create($data);
    }
}
