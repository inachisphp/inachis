<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authorisation;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;

final class PermissionResolver
{
    /**
     * Checks whether a user has a specific permission.
     */
    public function hasPermission(
        User $user,
        PermissionResource $resource,
        PermissionAction $action,
    ): bool {
        foreach ($user->getAssignedRoles() as $role) {
            foreach ($role->getRolePermissions() as $permission) {
                if (
                    $permission->getResource() === $resource
                    && $permission->getAction() === $action
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks a permission expressed as a string such as:
     *
     * PAGE_EDIT
     * PAGE_DELETE
     * USER_VIEW
     * ROLE_EDIT
     */
    public function isGranted(
        User $user,
        string $permission,
    ): bool {
        [$resource, $action] = $this->parsePermission($permission);

        if (null === $resource || null === $action) {
            return false;
        }

        return $this->hasPermission(
            $user,
            $resource,
            $action,
        );
    }

    /**
     * Converts PAGE_EDIT into:
     *
     * PermissionResource::PAGE
     * PermissionAction::EDIT
     *
     * @return array{
     *     0: PermissionResource|null,
     *     1: PermissionAction|null
     * }
     */
    private function parsePermission(string $permission): array
    {
        foreach (PermissionAction::cases() as $action) {
            $suffix = '_'.$action->value;

            if (str_ends_with($permission, $suffix)) {
                $resourceName = substr(
                    $permission,
                    0,
                    -strlen($suffix),
                );

                return [
                    PermissionResource::tryFrom($resourceName),
                    $action,
                ];
            }
        }

        return [null, null];
    }
}
