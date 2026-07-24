<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum\Security;

/**
 * Defines the authentication assurance required for an account.
 */
enum AuthenticationPolicy: string
{
    /**
     * Only the primary authentication method is required.
     *
     * Typically username/password or configured single-factor login.
     */
    case PASSWORD_REQUIRED = 'password_required';

    /**
     * Any configured multi-factor authentication method is acceptable.
     */
    case MFA_REQUIRED = 'mfa_required';

    /**
     * Time-based One Time Password authentication is required.
     */
    case TOTP_REQUIRED = 'totp_required';

    /**
     * WebAuthn authentication is required.
     *
     * This includes passkeys and hardware security keys.
     */
    case WEBAUTHN_REQUIRED = 'webauthn_required';

    /**
     * Returns a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PASSWORD_REQUIRED => 'Password Only',
            self::MFA_REQUIRED => 'Multi-factor Authentication (TOTP or WebAuthn)',
            self::TOTP_REQUIRED => 'Temporary One-Time-Password',
            self::WEBAUTHN_REQUIRED => 'WebAuthn Passkey',
        };
    }
}
