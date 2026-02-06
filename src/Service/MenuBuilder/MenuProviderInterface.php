<?php

namespace Inachis\Service\MenuBuilder;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('cms.menu_provider')]
interface MenuProviderInterface
{
    public function getMenuItems(): array;
}
