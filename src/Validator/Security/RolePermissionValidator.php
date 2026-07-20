<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Validator\Security;

use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;

final class RolePermissionValidator
{
    /**
     * Validate a permission matrix.
     *
     * @param array<string, array<string, mixed>> $permissions
     * @return string[]
     */
    public function validate(array $permissions): array
    {
        $warnings = [];

        foreach ($permissions as $resource => $actions) {
            $resourceEnum = PermissionResource::tryFrom($resource);

            if ($resourceEnum === null || !is_array($actions)) {
                continue;
            }

            $granted = array_map(
                static fn(string $action): ?PermissionAction => PermissionAction::tryFrom($action),
                array_keys(array_filter($actions))
            );

            // Remove invalid actions
            $granted = array_filter(
                $granted,
                static fn(?PermissionAction $action): bool => $action !== null
            );

            foreach ($granted as $action) {
                foreach ($this->expandRequirements($action) as $required) {
                    if (!in_array($required, $granted, true)) {
                        $warnings[] = sprintf(
                            '%s has %s permission but not %s.',
                            $resourceEnum->label(),
                            $action->label(),
                            $required->label()
                        );
                    }
                }
            }
        }

        return array_unique($warnings);
    }

    /**
     * Recursively expands all requirements for an action.
     * 
     * @return PermissionAction[]
     */
    private function expandRequirements(PermissionAction $action): array
    {
        $requirements = [];

        foreach ($action->requires() as $required) {
            $requirements[] = $required;

            $requirements = array_merge(
                $requirements,
                $this->expandRequirements($required)
            );
        }

        return array_values(
            array_unique($requirements, SORT_REGULAR)
        );
    }
}
