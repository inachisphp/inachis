<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Security\Attribute;

use Attribute;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class RequiresPermission
{
    /**
     * Undocumented function
     *
     * @param PermissionResource|array<PermissionResource> $resource
     * @param PermissionAction $action
     */
    public function __construct(
        public PermissionResource|array $resource,
        public PermissionAction $action,
    ) {}

    /**
     * @return list<PermissionResource>
     */
    public function resources(): array
    {
        return is_array($this->resource)
            ? $this->resource
            : [$this->resource];
    }
}
