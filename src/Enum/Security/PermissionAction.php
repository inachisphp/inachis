<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum\Security;

enum PermissionAction: string
{
    case MANAGE = 'MANAGE';
    case CREATE = 'CREATE';
    case VIEW = 'VIEW';
    case EDIT = 'EDIT';
    case DELETE = 'DELETE';
    case REVIEW = 'REVIEW';
    case PUBLISH = 'PUBLISH';

    /**
     * Returns a friendly name for the {@link PermissionAction}
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::MANAGE => 'Administer',
            self::CREATE => 'Create',
            self::VIEW => 'View',
            self::EDIT => 'Edit',
            self::DELETE => 'Delete',
            self::REVIEW => 'Review',
            self::PUBLISH => 'Publish',
        };
    }

    /**
     * Defines action inheritance, for example, having the ability to create
     * something implies they can edit and view it.
     *
     * @return list<PermissionAction>
     */
    public function requires(): array
    {
        return match ($this) {
            self::PUBLISH => [
                self::REVIEW,
                self::EDIT,
                self::VIEW,
            ],

            self::CREATE,
            self::REVIEW => [
                self::EDIT,
                self::VIEW,
            ],

            self::EDIT => [
                self::VIEW,
            ],

            self::DELETE => [
                self::VIEW,
            ],

            default => [],
        };
    }
}
