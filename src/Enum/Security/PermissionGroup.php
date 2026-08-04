<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Enum\Security;

/**
 * Enum used to represent permission groups for {@link Role}.
 */
enum PermissionGroup: string
{
    case CONTENT = 'CONTENT';
    case USERS = 'USERS';
    case SECURITY = 'SECURITY';
    case SETTINGS = 'SETTINGS';
    case TOOLS = 'TOOLS';

    /**
     * The user-friendly label for the group.
     */
    public function label(): string
    {
        return match ($this) {
            self::CONTENT => 'Content',
            self::USERS => 'Users & Permissions',
            self::SECURITY => 'Security & Privacy',
            self::SETTINGS => 'Settings',
            self::TOOLS => 'Tools',
        };
    }

    /**
     * Suitable material-icon graphic to represent the permission group.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CONTENT => 'article',
            self::USERS => 'admin_panel_settings',
            self::SECURITY => 'security',
            self::SETTINGS => 'settings',
            self::TOOLS => 'construction',
        };
    }
}
