<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Twig;

use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionGroup;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Security\Authorisation\PermissionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Permissions helper for Twig that allows easy checking of permissions for the
 * current user to determine if elements should be displayed.
 */
final class PermissionExtension extends AbstractExtension
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly PermissionManager $permissionManager,
        private readonly Security $security,
    ) {
    }

    /**
     * Returns the Twig functions exposed by this extension.
     *
     * @return list<TwigFunction> The array of callable functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('can', $this->can(...)),
            new TwigFunction('canAny', $this->canAny(...)),
            new TwigFunction('canGroup', $this->canGroup(...)),
            new TwigFunction('canResource', $this->canResource(...)),
            new TwigFunction('canWaste', $this->canWaste(...)),
        ];
    }

    /**
     * Checks whether the current user has permission for one or more actions on
     * the given resource.
     *
     * Examples:
     * - can('PAGE', 'EDIT')
     * - can('PAGE', ['VIEW', 'EDIT'])
     *
     * If $requireAll is true, the user must have every action. Otherwise, having
     * any one action is sufficient.
     *
     * @param PermissionAction|string|list<PermissionAction|string> $actions
     * @param bool $requireAll whether all actions are required
     */
    public function can(
        PermissionResource|string $resource,
        PermissionAction|string|array $actions,
        bool $requireAll = false,
    ): bool {
        /** @var \Inachis\Entity\User\User|null */
        $user = $this->security->getUser();
        if (null === $user) {
            return false;
        }

        $resource = $this->normaliseResource($resource);
        $actions = array_map(
            fn (PermissionAction|string $action) => $this->normaliseAction($action),
            (array) $actions,
        );

        foreach ($actions as $action) {
            $allowed = $this->permissionManager->can(
                $user,
                $resource,
                $action,
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
     * Checks whether the current user has any of the supplied permissions.
     *
     * @param list<array{
     *     0: PermissionResource|string,
     *     1: PermissionAction|string|list<PermissionAction|string>
     * }> $checks
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

    /**
     * Checks if the user has permissions for any {@link PermissionResource} in
     * this group.
     */
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

    /**
     * Checks if the user has any permission for the given {@link PermissionResource}.
     */
    public function canResource(
        PermissionResource|string $resource,
    ): bool {
        $resource = $this->normaliseResource($resource);

        return $this->can(
            $resource,
            $resource->actions(),
        );
    }

    /**
     * Checks if the user needs access to the Waste bin based on what resources
     * they have delete access for.
     */
    public function canWaste(): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof \Inachis\Entity\User\User) {
            return false;
        }

        return $this->permissionManager->canAccessWaste($user);
    }

    /**
     * Normalise string or PermissionResource into a PermissionResource.
     */
    private function normaliseResource(
        PermissionResource|string $resource,
    ): PermissionResource {
        if ($resource instanceof PermissionResource) {
            return $resource;
        }

        return PermissionResource::from(strtoupper($resource));
    }

    /**
     * Normalise string or PermissionAction into a PermissionAction.
     */
    private function normaliseAction(
        PermissionAction|string $action,
    ): PermissionAction {
        if ($action instanceof PermissionAction) {
            return $action;
        }

        return PermissionAction::from(strtoupper($action));
    }

    /**
     * Normalise a string or PermissionGroup into a PermissionGroup.
     */
    private function normaliseGroup(
        PermissionGroup|string $group,
    ): PermissionGroup {
        if ($group instanceof PermissionGroup) {
            return $group;
        }

        return PermissionGroup::from(strtoupper($group));
    }
}
