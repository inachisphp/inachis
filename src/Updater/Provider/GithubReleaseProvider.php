<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Updater\Provider;

use Inachis\Updater\Downloader\Downloader;
use Inachis\Updater\Release\Manifest;
use Inachis\Updater\Release\ManifestFactory;
use JsonException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GithubReleaseProvider implements ReleaseProviderInterface
{
    private const API = 'https://api.github.com/repos/%s/%s/releases';

    public function __construct(
        #[Autowire(value: 'inachisphp')]
        private string $owner,
        #[Autowire(value: 'inachis')]
        private string $repository,
        private Downloader $downloader,
        private ManifestFactory $manifestFactory = new ManifestFactory(),
    ) {}

    public function latest(): Manifest
    {
        return $this->fetchRelease(
            sprintf(self::API . '/latest', $this->owner, $this->repository)
        );
    }

    public function version(string $version): Manifest
    {
        return $this->fetchRelease(
            sprintf(self::API . '/tags/%s', $this->owner, $this->repository, rawurlencode($version))
        );
    }

    public function download(Manifest $manifest, string $destination): void
    {
        if (empty($manifest->archiveUrl)) {
            throw new RuntimeException('Manifest does not contain a valid archive download URL.');
        }

        $this->downloader->download($manifest->archiveUrl, $destination);
    }

    private function fetchRelease(string $url): Manifest
    {
        $release = $this->requestJson($url);

        if (!isset($release['assets']) || !is_array($release['assets'])) {
            throw new RuntimeException('GitHub release does not contain any assets.');
        }

        $manifestAsset = null;
        $assetsByName = [];

        foreach ($release['assets'] as $asset) {
            if (isset($asset['name'], $asset['browser_download_url'])) {
                $assetsByName[$asset['name']] = $asset['browser_download_url'];
                if (str_ends_with($asset['name'], '.json')) {
                    $manifestAsset = $asset;
                }
            }
        }

        if ($manifestAsset === null) {
            throw new RuntimeException('No release manifest JSON asset found.');
        }

        $manifestData = $this->requestJson($manifestAsset['browser_download_url']);
        $manifest = $this->manifestFactory->create($manifestData);

        // Match the ZIP archive URL specified in the manifest to the GitHub asset URL
        if (isset($assetsByName[$manifest->package])) {
            // UPDATED: Populate archiveUrl, releaseNotes, and publishedAt from GitHub API payload
            return new Manifest(
                version: $manifest->version,
                minimumVersion: $manifest->minimumVersion,
                package: $manifest->package,
                packageSha256: $manifest->packageSha256,
                migrations: $manifest->migrations,
                preserve: $manifest->preserve,
                replace: $manifest->replace,
                archiveUrl: $assetsByName[$manifest->package],
                type: $manifest->type,
                releaseNotes: (string) ($release['body'] ?? ''),
                publishedAt: (string) ($release['published_at'] ?? ''),
            );
        }

        throw new RuntimeException(
            sprintf('Release package "%s" listed in manifest was not found in release assets.', $manifest->package)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function requestJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'header' => implode("\r\n", [
                    'User-Agent: Inachis-Updater/1.0',
                    'Accept: application/vnd.github+json',
                ]),
                'timeout' => 30,
                'follow_location' => true,
            ],
        ]);

        $contents = @file_get_contents($url, false, $context);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to fetch data from "%s".', $url));
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('Invalid JSON received from "%s".', $url), previous: $exception);
        }

        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Unexpected JSON structure from "%s".', $url));
        }

        return $data;
    }
}
