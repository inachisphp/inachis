<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */
namespace Inachis\Service\MenuBuilder;

use Inachis\Service\Plugin\PluginManager;

/**
 * Builds the navigation menu by combining menu items from all enabled menu providers
 */
final class MenuBuilder
{
    /**
     * @param PluginManager $pluginManager
     * @param iterable<MenuProviderInterface> $menuProviders
     */
    public function __construct(
        private PluginManager $pluginManager,
        private iterable $menuProviders
    ) {}

    /**
     * Builds the navigation menu by combining menu items from all enabled menu providers
     * 
     * @return array<int, array{label: string, url: string, priority: int}> The navigation menu
     */
    public function build(): array
    {
        $items = [];

        foreach ($this->menuProviders as $provider) {
            $pluginClass = $provider::class;

            if ($this->pluginManager->isEnabled($pluginClass)) {
                $items = array_merge($items, $provider->getMenuItems());
            }
        }
        usort($items, fn($a, $b) => ($a['priority'] ?? 0) <=> ($b['priority'] ?? 0));

        return $items;
    }
}
