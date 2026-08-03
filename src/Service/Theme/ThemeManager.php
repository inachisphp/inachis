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
	 * Returns the identifier for the currently active theme
	 *
	 * @return string
	 */
    public function getActiveThemeIdentifier(): string
    {
        return $this->settings->getValue(self::SETTING_ACTIVE_THEME)
            ?? 'default';
    }

	/**
	 * Sets the active theme based on the provided identifier
	 *
	 * @param string $identifier
	 */
	public function setActiveTheme(string $identifier): void
	{
		$this->settings->setValue(self::SETTING_ACTIVE_THEME, $identifier);
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

            if ($cachedTheme instanceof ThemeDto && $cachedTheme->isCompatible) {
                return $cachedTheme;
            }
        }

        $identifier = $this->getActiveThemeIdentifier();
        $theme = $this->themeScanner->getTheme($identifier);

        if (null === $theme) {
            $theme = $this->createFallbackTheme($identifier);
            $theme->isFallback = true;
            $theme->requestedIdentifier = $identifier;
            $theme->fallbackReason = 'not_found';
        }
        elseif (!$theme->isCompatible) {
            $incompatibleTheme = $theme;
            $theme = $this->createFallbackTheme('default');
            $theme->isFallback = true;
            $theme->requestedIdentifier = $identifier;
            $theme->fallbackReason = 'incompatible_version';
            $theme->requiredInachisVersion = $incompatibleTheme->requiredInachisVersion;
        }

        $cacheItem->set($theme);
        $this->cache->save($cacheItem);

        return $theme;
    }

	/**
	 * Returns the result of testing if the theme specified by the identifier is installed
	 *
	 * @param string $identifier
	 * @return boolean
	 */
    public function isThemeInstalled(string $identifier): bool
    {
        return null !== $this->themeScanner->getTheme($identifier);
    }

	/**
	 * Gets the folder path for the currently active theme to use in the
	 * Twig YAML configuration for paths
	 *
	 * @return string
	 */
    public function getActiveThemePath(): string
    {
        $identifier = $this->getActiveThemeIdentifier();
        $themePaths = [
            // sprintf('%s/themes/%s', $this->projectDir, $identifier),
            sprintf('%s/templates/themes/%s', $this->projectDir, $identifier),
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
            $this->getActiveThemeIdentifier(),
            ltrim($relativePath, '/')
        );
    }

	/**
	 * Returns a model of a fallback theme for when active theme fails
	 * to load.
	 *
	 * @param string $identifier
	 * @return ThemeDto
	 */
    private function createFallbackTheme(string $identifier): ThemeDto
    {
        $theme = new ThemeDto();
        $theme->identifier = $identifier;
        $theme->name = ucfirst($identifier) . ' Theme';
        $theme->version = '1.0.0';
        $theme->author = '';
        $theme->description = '';
        $theme->path = $this->getActiveThemePath();
        $theme->screenshot = null;

        return $theme;
    }
}
