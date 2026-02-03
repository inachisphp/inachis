<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */
namespace Inachis\Service\MenuBuilder;

use Inachis\Plugin\PluginManager;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class MenuBuilder
{
    public function __construct(
        private PluginManager $pluginManager,
        #[TaggedIterator('cms.menu_provider')]
        private iterable $menuProviders
    ) {}

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
