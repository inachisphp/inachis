<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater\Provider;

use Inachis\Updater\Downloader\Downloader;
use Inachis\Updater\Release\Manifest;
use Inachis\Updater\Release\ManifestFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class GithubReleaseProvider implements ReleaseProviderInterface
{
    private const API = 'https://api.github.com/repos/%s/%s/releases';

    /**
     * @param int $cacheTtl Cache TTL in seconds (default: 3600 / 1 hour)
     */
    public function __construct(
        #[Autowire(value: 'inachisphp')]
        private string $owner,
        #[Autowire(value: 'inachis')]
        private string $repository,
        private Downloader $downloader,
        private CacheInterface $cache,
        private ManifestFactory $manifestFactory = new ManifestFactory(),
        private int $cacheTtl = 3600,
    ) {
    }

    public function latest(): Manifest
    {
        $cacheKey = sprintf('inachis_updater_latest_%s_%s', $this->owner, $this->repository);

        return $this->cache->get($cacheKey, function (ItemInterface $item): Manifest {
            $item->expiresAfter($this->cacheTtl);

            return $this->fetchRelease(
                sprintf(self::API.'/latest', $this->owner, $this->repository),
            );
        });
    }

    public function version(string $version): Manifest
    {
        $cacheKey = sprintf(
            'inachis_updater_version_%s_%s_%s',
            $this->owner,
            $this->repository,
            md5($version),
        );

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($version): Manifest {
            $item->expiresAfter($this->cacheTtl);

            return $this->fetchRelease(
                sprintf(self::API.'/tags/%s', $this->owner, $this->repository, rawurlencode($version)),
            );
        });
    }

    public function download(Manifest $manifest, string $destination): void
    {
        if (empty($manifest->archiveUrl)) {
            throw new \RuntimeException('Manifest does not contain a valid archive download URL.');
        }

        $this->downloader->download($manifest->archiveUrl, $destination);
    }

    private function fetchRelease(string $url): Manifest
    {
        $release = $this->requestJson($url);

        if (!isset($release['assets']) || !is_array($release['assets'])) {
            throw new \RuntimeException('GitHub release does not contain any assets.');
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

        if (null === $manifestAsset) {
            throw new \RuntimeException('No release manifest JSON asset found.');
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

        throw new \RuntimeException(sprintf('Release package "%s" listed in manifest was not found in release assets.', $manifest->package));
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

        if (false === $contents) {
            throw new \RuntimeException(sprintf('Unable to fetch data from "%s".', $url));
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(sprintf('Invalid JSON received from "%s".', $url), previous: $exception);
        }

        if (!is_array($data)) {
            throw new \RuntimeException(sprintf('Unexpected JSON structure from "%s".', $url));
        }

        return $data;
    }
}
