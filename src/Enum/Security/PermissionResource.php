<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum\Security;

use Inachis\Enum\Security\PermissionAction;

/**
 * Enum used to identify {@link Role} items
 */
enum PermissionResource: string
{
    case PAGE = 'PAGE';
    case SERIES = 'SERIES';
    case IMAGE = 'IMAGE';
    case TAG = 'TAG';
    case CATEGORY = 'CATEGORY';

    case USER = 'USER';
    case ROLE = 'ROLE';
    case PASSWORD_POLICY = 'PASSWORD_POLICY';
    case AUDIT_LOG = 'AUDIT_LOG';

    case ANALYTICS = 'ANALYTICS';
    case SYSTEM_STATUS = 'SYSTEM_STATUS';
    case EMAIL_DNS = 'EMAIL_DNS';
    case ERROR_LOG = 'ERROR_LOG';
    case STORAGE = 'STORAGE';
    case IMPORT_EXPORT = 'IMPORT_EXPORT';
    case MAINTENANCE = 'MAINTENANCE';
    // case BACKUP;

    // case SETTINGS = 'SETTINGS';
    case NAVIGATION = 'NAVIGATION';
    case THEME = 'THEME';
    case ROBOTS = 'ROBOTS';

    /**
     * @return list<PermissionAction>
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

            self::CATEGORY,
            self::IMAGE,
            self::NAVIGATION,
            self::TAG,
            self::USER => [
                PermissionAction::VIEW,
                PermissionAction::CREATE,
                PermissionAction::EDIT,
                PermissionAction::DELETE,
            ],

            // self::BACKUP => [
            //     PermissionAction::VIEW,
            //     PermissionAction::CREATE,
            // ],

            self::IMPORT_EXPORT,
            self::PASSWORD_POLICY,
            self::ROLE,
            self::THEME => [
                PermissionAction::MANAGE,
            ],

            self::MAINTENANCE => [
                PermissionAction::VIEW,
                PermissionAction::EDIT,
            ],

            self::ANALYTICS,
            self::AUDIT_LOG,
            self::EMAIL_DNS,
            self::ERROR_LOG,
            self::SYSTEM_STATUS,
            self::STORAGE => [
                PermissionAction::VIEW,
            ],

            self::ROBOTS => [ PermissionAction::EDIT ],
        };
    }

    /**
     * Returns a human-friendly label for the permission resource
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PAGE => 'Pages',
            self::SERIES => 'Series',
            self::IMAGE => 'Images',
            self::TAG => 'Tags',
            self::CATEGORY => 'Categories',

            self::USER => 'Users',
            self::ROLE => 'Roles',
            self::PASSWORD_POLICY => 'Password Policies',
            self::AUDIT_LOG => 'Audit Logs',

            self::ANALYTICS => 'Analytics',
            self::SYSTEM_STATUS => 'System Status',
            self::EMAIL_DNS => 'Email DNS',
            self::IMPORT_EXPORT => 'Import/Export',
            self::MAINTENANCE => 'Maintenance Mode',
            self::ERROR_LOG => 'Error Log',
            self::STORAGE => 'Storage',
            // self::BACKUP => 'Backups',

            self::NAVIGATION => 'Navigation',
            self::THEME => 'Themes',
            self::ROBOTS => 'robots.txt',
        };
    }

    /**
     * Return the contents of the permission groups
     *
     * @return list<array{group: PermissionGroup, resources:list<PermissionResource>}>
     */
    public static function grouped(): array
    {
        return [
            [
                'group' => PermissionGroup::CONTENT,
                'resources' => [
                    self::PAGE,
                    self::SERIES,
                    self::IMAGE,
                    self::CATEGORY,
                    self::TAG,
                ],
            ],
            [
                'group' => PermissionGroup::USERS,
                'resources' => [
                    self::USER,
                    self::ROLE,
                    self::PASSWORD_POLICY,
                ],
            ],
            [
                'group' => PermissionGroup::SETTINGS,
                'resources' => [
                    self::NAVIGATION,
                    self::THEME,
                    self::ROBOTS,
                ],
            ],
            [
                'group' => PermissionGroup::TOOLS,
                'resources' => [
                    self::ANALYTICS,
                    self::AUDIT_LOG,
                    self::SYSTEM_STATUS,
                    self::ERROR_LOG,
                    self::EMAIL_DNS,
                    self::IMPORT_EXPORT,
                    self::STORAGE,
                    self::MAINTENANCE,
                ],
            ],
        ];
    }
}
