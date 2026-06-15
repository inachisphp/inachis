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
use Inachis\Repository\System\SettingRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ThemeManager
{
	/** @var string */
    public const SETTING_ACTIVE_THEME = 'theme.active';

	/** @var string */
    private const CACHE_KEY_ACTIVE_THEME = 'theme.active.dto';

	/**
	 * Constructor for ThemeManager
	 *
	 * @param SettingRepository $settings
	 * @param ThemeScanner $themeScanner
	 * @param CacheItemPoolInterface $cache
	 * @param string $projectDir
	 */
    public function __construct(
        private SettingRepository $settings,
        private ThemeScanner $themeScanner,
    	private CacheItemPoolInterface $cache,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

	/**
	 * Returns the slug for the currently active theme
	 *
	 * @return string
	 */
    public function getActiveThemeSlug(): string
    {
        return $this->settings->getValue(self::SETTING_ACTIVE_THEME)
            ?? 'default';
    }

	/**
	 * Sets the active theme based on the provided slug
	 *
	 * @param string $slug
	 */
	public function setActiveTheme(string $slug): void
	{
		$this->settings->setValue(self::SETTING_ACTIVE_THEME, $slug);
		$this->cache->deleteItem(self::CACHE_KEY_ACTIVE_THEME);
		$this->cache->deleteItem('theme.twig.paths');
	}

	/**
	 * Returns a DTO (model) of the currently active theme
	 *
	 * @return ThemeDto
	 */
    public function getActiveTheme(): ThemeDto
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY_ACTIVE_THEME);

        if ($cacheItem->isHit()) {
            $cachedTheme = $cacheItem->get();

            if ($cachedTheme instanceof ThemeDto) {
                return $cachedTheme;
            }
        }

        $slug = $this->getActiveThemeSlug();
        $theme = $this->themeScanner->getTheme($slug) ?? $this->createFallbackTheme($slug);

        $cacheItem->set($theme);
        $this->cache->save($cacheItem);

        return $theme;
    }

	/**
	 * Returns the result of testing if the theme specified by the slug is installed
	 *
	 * @param string $slug
	 * @return boolean
	 */
    public function isThemeInstalled(string $slug): bool
    {
        return null !== $this->themeScanner->getTheme($slug);
    }

	/**
	 * Gets the folder path for the currently active theme to use in the
	 * Twig YAML configuration for paths
	 *
	 * @return string
	 */
    public function getActiveThemePath(): string
    {
        $slug = $this->getActiveThemeSlug();
        $themePaths = [
            // sprintf('%s/themes/%s', $this->projectDir, $slug),
            sprintf('%s/templates/themes/%s', $this->projectDir, $slug),
        ];

        foreach ($themePaths as $themePath) {
            if (is_dir($themePath)) {
                return $themePath;
            }
        }

        return $themePaths[0];
    }

	/**
	 * Returns the path to the default theme
	 *
	 * @return string
	 */
    public function getDefaultThemePath(): string
    {
        return sprintf('%s/templates/themes/default', $this->projectDir);
    }

	/**
	 * Returns the front-end Twig templates path for the currently active theme
	 *
	 * @return string
	 */
    public function getActiveThemeWebPath(): string
    {
        return $this->getActiveThemePath() . '/web';
    }

	/**
	 * Returns the path for the assets of the currently active theme
	 *
	 * @param string $relativePath
	 * @return string
	 */
    public function getAssetPath(string $relativePath): string
    {
        return sprintf(
            '/themes/%s/assets/%s',
            $this->getActiveThemeSlug(),
            ltrim($relativePath, '/')
        );
    }

	/**
	 * Returns a model of a fallback theme for when active theme fails
	 * to load.
	 *
	 * @param string $slug
	 * @return ThemeDto
	 */
    private function createFallbackTheme(string $slug): ThemeDto
    {
        $theme = new ThemeDto();
        $theme->slug = $slug;
        $theme->name = ucfirst($slug) . ' Theme';
        $theme->version = '1.0.0';
        $theme->author = '';
        $theme->description = '';
        $theme->path = $this->getActiveThemePath();
        $theme->screenshot = null;

        return $theme;
    }
}
