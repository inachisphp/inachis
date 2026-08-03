<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
    case DOWNLOAD = 'DOWNLOAD';
    case TAG = 'TAG';
    case CATEGORY = 'CATEGORY';

    case USER = 'USER';
    case ROLE = 'ROLE';
    case PASSWORD_POLICY = 'PASSWORD_POLICY';
    case AUDIT_LOG = 'AUDIT_LOG';

    case PRIVACY_GDPR = 'PRIVACY_GDPR';

    case ANALYTICS = 'ANALYTICS';
    case SYSTEM_STATUS = 'SYSTEM_STATUS';
    case EMAIL_DNS = 'EMAIL_DNS';
    case ERROR_LOG = 'ERROR_LOG';
    case STORAGE = 'STORAGE';
    case IMPORT_EXPORT = 'IMPORT_EXPORT';
    case MAINTENANCE = 'MAINTENANCE';
    case CSP_POLICY = 'CSP_POLICY';
    // case BACKUP;

    case NAVIGATION = 'NAVIGATION';
    case PLUGIN = 'PLUGIN';
    case THEME = 'THEME';
    case CRAWLER = 'CRAWLER';

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
            self::DOWNLOAD,
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
            self::PLUGIN,
            self::THEME => [
                PermissionAction::MANAGE,
            ],

            self::PRIVACY_GDPR,
            self::CSP_POLICY,
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

            self::CRAWLER => [
                PermissionAction::VIEW,
                PermissionAction::EDIT,
            ],
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
            self::DOWNLOAD => 'Downloads',
            self::TAG => 'Tags',
            self::CATEGORY => 'Categories',

            self::USER => 'Users',
            self::ROLE => 'Roles',
            self::PASSWORD_POLICY => 'Password Policies',
            self::AUDIT_LOG => 'Audit Logs',

            self::PRIVACY_GDPR => 'GDPR Policy',

            self::ANALYTICS => 'Analytics',
            self::SYSTEM_STATUS => 'System Status',
            self::EMAIL_DNS => 'Email DNS',
            self::IMPORT_EXPORT => 'Import/Export',
            self::MAINTENANCE => 'Maintenance Mode',
            self::ERROR_LOG => 'Error Log',
            self::STORAGE => 'Storage Usage',
            // self::BACKUP => 'Backups',
            self::CSP_POLICY => 'Content Security Policy',

            self::NAVIGATION => 'Navigation',
            self::PLUGIN => 'Plugins & Addons',
            self::THEME => 'Themes',
            self::CRAWLER => 'Crawlers and Discovery',
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
                    self::DOWNLOAD,
                    self::CATEGORY,
                    self::TAG,
                ],
            ],
            [
                'group' => PermissionGroup::USERS,
                'resources' => [
                    self::USER,
                    self::AUDIT_LOG,
                    self::ROLE,
                ],
            ],
            [
                'group' => PermissionGroup::SECURITY,
                'resources' => [
                    self::CSP_POLICY,
                    self::PRIVACY_GDPR,
                    self::EMAIL_DNS,
                    self::SYSTEM_STATUS,
                    self::PASSWORD_POLICY,
                ],
            ],
            [
                'group' => PermissionGroup::SETTINGS,
                'resources' => [
                    self::NAVIGATION,
                    self::CRAWLER,
                    self::PLUGIN,
                    self::THEME,
                ],
            ],
            [
                'group' => PermissionGroup::TOOLS,
                'resources' => [
                    self::ANALYTICS,
                    self::ERROR_LOG,
                    self::STORAGE,
                    self::IMPORT_EXPORT,
                    self::MAINTENANCE,
                ],
            ],
        ];
    }

    /**
     * @return list<PermissionResource>
     */
    public static function resourcesForGroup(
        PermissionGroup $group,
    ): array {
        foreach (self::grouped() as $permissionGroup) {
            if ($permissionGroup['group'] === $group) {
                return $permissionGroup['resources'];
            }
        }

        return [];
    }
}
