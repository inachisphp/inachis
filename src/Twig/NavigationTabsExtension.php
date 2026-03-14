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
use Inachis\Service\Navigation\NavigationTabService;

class NavigationTabsExtension extends AbstractExtension
{
    public function __construct(private NavigationTabService $navigation) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('navigation_tabs', [$this, 'getTabs']),
        ];
    }

    public function getTabs()
    {
        return $this->navigation->getActiveTabs();
    }
}