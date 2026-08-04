<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Twig;

use Inachis\Model\NavigationTabDto;
use Inachis\Service\Navigation\NavigationTabService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for navigation tabs.
 */
class NavigationTabsExtension extends AbstractExtension
{
    public function __construct(private NavigationTabService $navigation)
    {
    }

    /**
     * Returns the list of functions provided by this extension.
     *
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('navigation_tabs', [$this, 'getTabs']),
        ];
    }

    /**
     * Returns the list of active navigation tabs.
     *
     * @return list<NavigationTabDto>
     */
    public function getTabs(): array
    {
        return $this->navigation->getActiveTabs();
    }
}
