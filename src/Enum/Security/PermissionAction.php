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
    case VIEW = 'VIEW';
    case CREATE = 'CREATE';
    case EDIT = 'EDIT';
    case DELETE = 'DELETE';
    case REVIEW = 'REVIEW';
    case PUBLISH = 'PUBLISH';

    public function label(): string
    {
        return match ($this) {
            self::MANAGE => 'Manage',
            self::VIEW => 'View',
            self::CREATE => 'Create',
            self::EDIT => 'Edit',
            self::DELETE => 'Delete',
            self::REVIEW => 'Review',
            self::PUBLISH => 'Publish',
        };
    }
}
