<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authorisation;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;

final class PermissionManager
{
    /**
     * Cached permissions for the current user.
     *
     * @var array<string, array<string, bool>>
     */
    private array $permissions = [];

    private ?string $loadedUserIdentifier = null;

    public function can(
        ?User $user,
        PermissionResource $resource,
        PermissionAction $action
    ): bool {
        if ($user === null) {
            return false;
        }

        $this->loadPermissions($user);

        return $this->permissions[$resource->value][$action->value] ?? false;
    }

    /**
     * Returns every permission the user has.
     *
     * @return array<string, array<string, bool>>
     */
    public function all(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $this->loadPermissions($user);

        return $this->permissions;
    }

    /**
     * Loads permissions for the user
     *
     * @param User $user
     */
    private function loadPermissions(User $user): void
    {
        // Already built for this user during this request
        if ($this->loadedUserIdentifier === $user->getId()) {
            return;
        }

        $this->permissions = [];

        foreach ($user->getAssignedRoles() as $role) {
            foreach ($role->getRolePermissions() as $permission) {
                $this->permissions
                    [$permission->getResource()->value]
                    [$permission->getAction()->value] = true;
            }
        }

        $this->loadedUserIdentifier = $user->getId();
    }

    public function canAccessWaste(User $user): bool
    {
        foreach (PermissionResource::cases() as $resource) {
            if (in_array(PermissionAction::DELETE, $resource->actions(), true)
                && $this->can($user, $resource, PermissionAction::DELETE)
            ) {
                return true;
            }
        }

        return false;
    }
}
