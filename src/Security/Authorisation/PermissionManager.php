<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authorisation;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Ramsey\Uuid\UuidInterface;

final class PermissionManager
{
    /**
     * Cached permissions for the current user.
     *
     * @var array<string, array<string, bool>>
     */
    private array $permissions = [];

    private ?UuidInterface $loadedUserIdentifier = null;

    public function can(
        ?User $user,
        PermissionResource $resource,
        PermissionAction $action,
    ): bool {
        if (null === $user) {
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
        if (null === $user) {
            return [];
        }

        $this->loadPermissions($user);

        return $this->permissions;
    }

    /**
     * Loads permissions for the user.
     */
    private function loadPermissions(User $user): void
    {
        // Already built for this user during this request
        if ($this->loadedUserIdentifier?->equals($user->getId())) {
            return;
        }

        $this->permissions = [];

        foreach ($user->getAssignedRoles() as $role) {
            foreach ($role->getRolePermissions() as $permission) {
                $this->permissions[$permission->getResource()->value][$permission->getAction()->value] = true;
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
