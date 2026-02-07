<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */
namespace Inachis\Service\MenuBuilder;

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
     * @return array The menu items
     */
    public function getMenuItems(): array;
}
