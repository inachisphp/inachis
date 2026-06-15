<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */


namespace Inachis\Service\Theme;

use Inachis\Model\System\ThemeDto;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

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
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
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
     * @return array<ThemeDto>
     */
    public function scanThemes(array &$errors = []): array
    {
        $themes = [];
        $seenSlugs = [];

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

                if (isset($seenSlugs[$theme->slug])) {
                    $this->logger->warning(sprintf(
                        'Duplicate theme slug "%s" found.',
                        $theme->slug
                    ));
                    $errors[] = sprintf('Duplicate theme slug "%s" found.', $theme->slug);

                    continue;
                }

                $seenSlugs[$theme->slug] = true;
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
     * @param string $slug
     * @return ThemeDto|null
     */
    public function getTheme(string $slug): ?ThemeDto
    {
        foreach ($this->getThemes() as $theme) {
            if ($theme->slug === $slug) {
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

        if (!is_file($manifestFile)) {
            return null;
        }

        try {
            $manifest = Yaml::parseFile($manifestFile);
        } catch (ParseException $exception) {
            $this->logger->warning(sprintf(
                'Failed to parse theme manifest "%s": %s',
                $manifestFile,
                $exception->getMessage()
            ));

            return null;
        }

        if (!is_array($manifest)) {
            return null;
        }

        foreach (['slug', 'name'] as $requiredField) {
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

                return null;
            }
        }

        $screenshot = null;

        foreach (['screenshot.png', 'screenshot.jpg', 'screenshot.webp'] as $file) {
            if (is_file($themePath . '/' . $file)) {
                $screenshot = $themePath . '/' . $file;
                break;
            }
        }

        $theme = new ThemeDto();
        $theme->slug = (string) $manifest['slug'];
        $theme->name = (string) $manifest['name'];
        $theme->version = (string) ($manifest['version'] ?? '1.0.0');
        $theme->author = (string) ($manifest['author'] ?? '');
        $theme->description = (string) ($manifest['description'] ?? '');
        $theme->path = $themePath;
        $theme->screenshot = $screenshot;
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

    /**
     * Extracts a feature list for the given theme manifest
     *
     * @param array<string> $manifest
     * @param string $section
     * @return array<string>
     */
    private function extractFeatures(
        array $manifest,
        string $section
    ): array {
        $features = $manifest[$section]['features'] ?? [];

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
}
