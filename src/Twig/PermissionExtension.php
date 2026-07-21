<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Twig;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionGroup;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Security\Authorisation\PermissionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PermissionExtension extends AbstractExtension
{
    public function __construct(
        private readonly PermissionManager $permissionManager,
        private readonly Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('can', $this->can(...)),
            new TwigFunction('canAny', [$this, 'canAny']),
            new TwigFunction('canGroup', [$this, 'canGroup']),
            new TwigFunction('canResource', [$this, 'canResource']),
            new TwigFunction('canWaste', [$this, 'canWaste']),
        ];
    }

    public function can(
        PermissionResource|string $resource,
        PermissionAction|string|array $actions,
        bool $requireAll = false,
    ): bool {
        $user = $this->security->getUser();

        if ($user === null) {
            return false;
        }

        $resource = $this->normaliseResource($resource);

        $actions = array_map(
            fn (PermissionAction|string $action) => $this->normaliseAction($action),
            (array) $actions
        );

        foreach ($actions as $action) {
            $allowed = $this->permissionManager->can(
                $user,
                $resource,
                $action
            );

            if ($requireAll && !$allowed) {
                return false;
            }

            if (!$requireAll && $allowed) {
                return true;
            }
        }

        return $requireAll;
    }

    /**
     * @param array<array{0: PermissionResource|string, 1: PermissionAction|string|array}> $checks
     */
    public function canAny(array $checks): bool
    {
        foreach ($checks as [$resource, $action]) {
            if ($this->can($resource, $action)) {
                return true;
            }
        }

        return false;
    }

    public function canGroup(
        PermissionGroup|string $group,
    ): bool {
        $group = $this->normaliseGroup($group);

        foreach (PermissionResource::resourcesForGroup($group) as $resource) {
            if ($this->canResource($resource)) {
                return true;
            }
        }

        return false;
    }

    public function canResource(
        PermissionResource|string $resource,
    ): bool {
        $resource = $this->normaliseResource($resource);

        return $this->can(
            $resource,
            $resource->actions()
        );
    }

    public function canWaste(): bool
    {
        return $this->permissionManager->canAccessWaste(
            $this->security->getUser()
        );
    }

    private function normaliseResource(
        PermissionResource|string $resource,
    ): PermissionResource {
        if ($resource instanceof PermissionResource) {
            return $resource;
        }

        return PermissionResource::from(strtoupper($resource));
    }

    private function normaliseAction(
        PermissionAction|string $action,
    ): PermissionAction {
        if ($action instanceof PermissionAction) {
            return $action;
        }

        return PermissionAction::from(strtoupper($action));
    }

    private function normaliseGroup(
        PermissionGroup|string $group,
    ): PermissionGroup {
        if ($group instanceof PermissionGroup) {
            return $group;
        }

        return PermissionGroup::from(strtoupper($group));
    }
}
