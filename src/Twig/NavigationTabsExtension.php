<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Inachis\Entity\NavigationTab;
use Inachis\Service\Navigation\NavigationTabService;

/**
 * Twig extension for navigation tabs
 */
class NavigationTabsExtension extends AbstractExtension
{
    /**
     * @param NavigationTabService $navigation
     */
    public function __construct(private NavigationTabService $navigation) {}

    /**
     * Returns the list of functions provided by this extension
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('navigation_tabs', [$this, 'getTabs']),
        ];
    }

    /**
     * Returns the list of active navigation tabs
     * @return array<NavigationTab>
     */
    public function getTabs(): array
    {
        return $this->navigation->getActiveTabs();
    }
}