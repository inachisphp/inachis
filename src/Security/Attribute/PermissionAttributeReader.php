<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Security\Attribute;

use ReflectionMethod;

final class PermissionAttributeReader
{
    /**
     * @return list<RequiresPermission>
     */
    public function getPermissions(object $controller, string $method): array
    {
        $reflection = new ReflectionMethod($controller, $method);

        return array_map(
            static fn ($attribute) => $attribute->newInstance(),
            $reflection->getAttributes(RequiresPermission::class)
        );
    }
}
