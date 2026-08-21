<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Package;

use Inachis\Enum\System\PackageType;
use Inachis\Model\System\Package;
use Inachis\Model\System\Plugin;
use Inachis\Model\System\Theme;
use Inachis\Service\Plugin\PluginScanner;
use Inachis\Service\Theme\ThemeScanner;

final readonly class PackageManager
{
    public function __construct(
        private ThemeScanner $themeScanner,
        private PluginScanner $pluginScanner,
    ) {
    }

    /**
     * Returns all installed packages.
     *
     * @return list<Package>
     */
    public function getPackages(): array
    {
        return array_merge(
            $this->themeScanner->getThemes(),
            $this->pluginScanner->getPlugins(),
        );
    }

    /**
     * Returns all installed themes.
     *
     * @return list<Theme>
     */
    public function getThemes(): array
    {
        return $this->themeScanner->getThemes();
    }

    /**
     * Returns all installed plugins.
     *
     * @return list<Plugin>
     */
    public function getPlugins(): array
    {
        return $this->pluginScanner->getPlugins();
    }

    /**
     * Returns a package.
     */
    public function getPackage(
        PackageType $type,
        string $identifier,
    ): ?Package {
        return match ($type) {
            PackageType::Core => throw new \LogicException('The core package is not managed by PackageManager.'),
            PackageType::Theme => $this->themeScanner->getTheme($identifier),
            PackageType::Plugin => $this->pluginScanner->getPlugin($identifier),
        };
    }

    /**
     * Tests whether a package is installed.
     */
    public function isInstalled(
        PackageType $type,
        string $identifier,
    ): bool {
        return null !== $this->getPackage(
            $type,
            $identifier,
        );
    }

    /**
     * Rescans all package types.
     */
    public function rescan(): void
    {
        $this->themeScanner->rescanThemes();
        $this->pluginScanner->rescanPlugins();
    }
}
