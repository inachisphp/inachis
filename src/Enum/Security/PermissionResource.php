<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum\Security;

use Inachis\Enum\Security\PermissionAction;

enum PermissionResource: string
{
    case PAGE = 'PAGE';
    case SERIES = 'SERIES';
    case IMAGE = 'IMAGE';
    case TAG = 'TAG';
    case CATEGORY = 'CATEGORY';

    /**
     * @return PermissionAction[]
     */
    public function actions(): array
    {
        return match ($this) {
            self::PAGE,
            self::SERIES => [
                PermissionAction::VIEW,
                PermissionAction::CREATE,
                PermissionAction::EDIT,
                PermissionAction::DELETE,
                PermissionAction::REVIEW,
                PermissionAction::PUBLISH,
            ],

            self::IMAGE,
            self::TAG,
            self::CATEGORY => [
                PermissionAction::VIEW,
                PermissionAction::CREATE,
                PermissionAction::EDIT,
                PermissionAction::DELETE,
            ],
        };
    }
}
