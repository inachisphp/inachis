<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Twig;

use Inachis\Service\Theme\FeatureRegistry;
use Inachis\Service\Theme\ThemeManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Theme Extension for Twig.
 */
final class ThemeExtension extends AbstractExtension
{
    /**
     * Constructor for ThemeExtension.
     */
    public function __construct(
        private ThemeManager $themeManager,
        private FeatureRegistry $featureRegistry,
    ) {
    }

    /**
     * Registers functions used by this Twig Extension.
     *
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('feature_enabled', [$this, 'featureEnabled']),
            new TwigFunction('plugin_enabled', [$this, 'pluginEnabled']),
            new TwigFunction('theme_asset', [$this, 'themeAsset']),
        ];
    }

    /**
     * Returns the result of testing if named feature is enabled.
     */
    public function featureEnabled(string $feature): bool
    {
        return $this->featureRegistry->has($feature);
    }

    /**
     * Returns the result of testing if the named plugin is enabled.
     */
    public function pluginEnabled(string $plugin): bool
    {
        return $this->featureRegistry->has($plugin);
    }

    /**
     * Returns the asset path based on the relative path.
     */
    public function themeAsset(string $relativePath): string
    {
        return $this->themeManager->getAssetPath($relativePath);
    }
}
