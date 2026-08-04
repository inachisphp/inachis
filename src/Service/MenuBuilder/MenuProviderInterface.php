<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\MenuBuilder;

use Inachis\Entity\System\MenuItem;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Interface for menu providers.
 */
#[AutoconfigureTag('cms.menu_provider')]
interface MenuProviderInterface
{
    /**
     * Get the menu items for the current user.
     *
     * @return array<MenuItem> The menu items
     */
    public function getMenuItems(): array;
}
