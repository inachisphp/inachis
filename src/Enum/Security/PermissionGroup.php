<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum\Security;

/**
 * Enum used to represent permission groups for {@link Role}
 */
enum PermissionGroup: string
{
    case CONTENT = 'CONTENT';
    case USERS = 'USERS';
    case SETTINGS = 'SETTINGS';
    case TOOLS = 'TOOLS';

    /**
     * The user-friendly label for the group
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::CONTENT => 'Content',
            self::USERS => 'Users & Security',
            self::SETTINGS => 'Settings',
            self::TOOLS => 'Tools',
        };
    }

    /**
     * Suitable material-icon graphic to represent the permission group
     *
     * @return string
     */
    public function icon(): string
    {
        return match ($this) {
            self::CONTENT => 'article',
            self::USERS => 'admin_panel_settings',
            self::SETTINGS => 'settings',
            self::TOOLS => 'construction',
        };
    }
}
