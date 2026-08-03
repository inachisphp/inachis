<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Theme;

use Composer\Semver\Semver;
use Inachis\Model\System\ThemeDto;
use Inachis\Service\ManifestLoader;
use Inachis\Service\System\VersionService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ThemeScanner
{
    /** @var string */
    private const CACHE_KEY_THEMES = 'themes.installed';
    /** @var string */
    private const CACHE_KEY_SCAN_STATUS = 'themes.scan.status';

    /**
     * Constructor for the ThemeScanner service
     *
     * @param string $projectDir
     * @param CacheItemPoolInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private VersionService $versionService,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
        private ManifestLoader $manifestLoader,
    ) {}

    /**
     * Gets an array of installed themes
     *
     * @return array<ThemeDto>
     */
    public function getThemes(): array
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY_THEMES);

        if ($cacheItem->isHit()) {
            /** @var array<ThemeDto>|null */
            $themes = $cacheItem->get();

            if (is_array($themes)) {
                return $themes;
            }
        }

        return $this->rescanThemes();
    }

    /**
     * Returns the results of the last theme directory scan
     *
     * @return array{lastScannedAt: int|null, errorCount: int, errors: string[]}
     */
    public function getScanStatus(): array
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY_SCAN_STATUS);

        if ($cacheItem->isHit()) {
            /** @var array{lastScannedAt: int|null, errorCount: int, errors: string[]}|null */
            $status = $cacheItem->get();

            if (is_array($status)) {
                return array_merge([
                    'lastScannedAt' => null,
                    'errorCount' => 0,
                    'errors' => [],
                ], $status);
            }
        }

        return [
            'lastScannedAt' => null,
            'errorCount' => 0,
            'errors' => [],
        ];
    }

    /**
     * Triggers a scan of the themes folder, caches the result, and caches
     * the result of the scan
     *
     * @return array<ThemeDto>
     */
    public function rescanThemes(): array
    {
        $errors = [];
        $themes = $this->scanThemes($errors);

        $themesCache = $this->cache->getItem(self::CACHE_KEY_THEMES);
        $themesCache->set($themes);
        $this->cache->save($themesCache);

        $statusCache = $this->cache->getItem(self::CACHE_KEY_SCAN_STATUS);
        $statusCache->set([
            'lastScannedAt' => time(),
            'errorCount' => count($errors),
            'errors' => $errors,
        ]);
        $this->cache->save($statusCache);

        return $themes;
    }

    /**
     * Scans the themes directory for a list of themes
     *
     * @param list<string> &$errors
     * @return array<ThemeDto>
     */
    public function scanThemes(array &$errors = []): array
    {
        $themes = [];
        $seenIdentifiers = [];

        foreach ($this->getThemeRoots() as $themesDirectory) {
            if (!is_dir($themesDirectory)) {
                continue;
            }

            foreach (scandir($themesDirectory) ?: [] as $directory) {
                if ('.' === $directory || '..' === $directory) {
                    continue;
                }

                $theme = $this->loadTheme(
                    $themesDirectory . DIRECTORY_SEPARATOR . $directory
                );

                if (null === $theme) {
                    $errors[] = sprintf('Skipped invalid theme at %s', $themesDirectory . DIRECTORY_SEPARATOR . $directory);
                    continue;
                }

                if (isset($seenIdentifiers[$theme->identifier])) {
                    $this->logger->warning(sprintf(
                        'Duplicate theme identifier "%s" found.',
                        $theme->identifier
                    ));
                    $errors[] = sprintf('Duplicate theme identifier "%s" found.', $theme->identifier);

                    continue;
                }

                $seenIdentifiers[$theme->identifier] = true;
                $themes[] = $theme;
            }
        }

        usort(
            $themes,
            static fn (ThemeDto $a, ThemeDto $b): int => strcasecmp($a->name, $b->name)
        );

        return $themes;
    }

    /**
     * Gets a specific theme
     *
     * @param string $identifier
     * @return ThemeDto|null
     */
    public function getTheme(string $identifier): ?ThemeDto
    {
        foreach ($this->getThemes() as $theme) {
            if ($theme->identifier === $identifier) {
                return $theme;
            }
        }

        return null;
    }

    /**
     * Loads details of a specific theme
     *
     * @param string $themePath
     * @return ThemeDto|null
     */
    private function loadTheme(string $themePath): ?ThemeDto
    {
        if (!is_dir($themePath)) {
            return null;
        }

        $manifestFile = $themePath . '/theme.yaml';
        $manifest = $this->manifestLoader->load($manifestFile);

        if (null === $manifest) {
            return null;
        }

        if (!$this->isValidManifest($manifest, $themePath)) {
            return null;
        }

        return $this->createThemeDto(
            $themePath,
            $manifest
        );
    }

    /**
     * Extracts a feature list for the given theme manifest
     *
     * @param array<mixed> $manifest
     * @param string $section
     * @return list<string>|array{}
     */
    private function extractFeatures(
        array $manifest,
        string $section
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
     * Reutns a list of folders to look for themes
     *
     * @return list<string>
     */
    private function getThemeRoots(): array
    {
        return [
            // $this->projectDir . '/themes',
            $this->projectDir . '/templates/themes',
        ];
    }

    private function isValidManifest(
        array $manifest,
        string $themePath
    ): bool {
        foreach (['identifier', 'name'] as $requiredField) {
            if (
                !isset($manifest[$requiredField]) ||
                !is_string($manifest[$requiredField]) ||
                '' === trim($manifest[$requiredField])
            ) {
                $this->logger->warning(sprintf(
                    'Theme "%s" is missing required field "%s".',
                    basename($themePath),
                    $requiredField
                ));

                return false;
            }
        }
        return true;
    }

    private function createThemeDto(
        string $themePath,
        array $manifest
    ): ThemeDto {
        $screenshot = null;
        foreach (['screenshot.png', 'screenshot.jpg', 'screenshot.webp'] as $file) {
            if (is_file($themePath . '/' . $file)) {
                $screenshot = $themePath . '/' . $file;
                break;
            }
        }

        $theme = new ThemeDto();
        $theme->identifier = (string) $manifest['identifier'];
        $theme->name = (string) $manifest['name'];
        $theme->version = is_string($manifest['version']) ? $manifest['version'] : '1.0.0';
        $theme->author = is_string($manifest['author']) ? $manifest['author'] : '';
        $theme->description = is_string($manifest['description']) ? $manifest['description'] : '';
        $theme->path = $themePath;
        $theme->screenshot = $screenshot;

        $requires = $manifest['requires'] ?? [];
        if (is_array($requires) && isset($requires['inachis']) && is_string($requires['inachis'])) {
            $theme->requiredInachisVersion = $requires['inachis'];
            $theme->isCompatible = $this->versionService->satisfies($theme->requiredInachisVersion);
        }

        $theme->requiredFeatures = $this->extractFeatures(
            $manifest,
            'requires'
        );
        $theme->suggestedFeatures = $this->extractFeatures(
            $manifest,
            'suggests'
        );

        return $theme;
    }
}
