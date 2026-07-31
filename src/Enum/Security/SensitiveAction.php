<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum\Security;

enum SensitiveAction: string
{
    case USER_MANAGEMENT = 'USER_MANAGEMENT';
    case ROLE_MANAGEMENT = 'ROLE_MANAGEMENT';

    case SECURITY_CONFIGURATION_CHANGE = 'SECURITY_CONFIGURATION_CHANGE';

    case USER_DISABLE = 'USER_DISABLE';
    case USER_EMAIL_CHANGE = 'USER_EMAIL_CHANGE';
    case USER_PASSWORD_RESET = 'USER_PASSWORD_RESET';
    case MFA_RESET = 'MFA_RESET';

    case DATA_IMPORT_EXPORT = 'DATA_IMPORT_EXPORT';
    case BACKUP_RESTORE = 'BACKUP_RESTORE';

    /**
     * Returns a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::USER_MANAGEMENT => 'Manage Users',
            self::ROLE_MANAGEMENT => 'Manage Roles',

            self::SECURITY_CONFIGURATION_CHANGE => 'Modify Security Policy',

            self::USER_DISABLE => 'Disable User',

            self::USER_EMAIL_CHANGE => 'Change User Email Address',
            self::USER_PASSWORD_RESET => 'Reset User Password',
            self::MFA_RESET => 'Reset Multi-factor Authentication',

            self::DATA_IMPORT_EXPORT => 'Import/Export Data',
            self::BACKUP_RESTORE => 'Backup/Restore Data',
            
            // self::API_CREDENTIAL_MANAGEMENT => 'Manage API Credentials',
            // self::USER_IMPERSONATE => 'Impersonate User',
            // self::SESSION_REVOCATION => 'Revoke User Sessions',
        };
    }
}
