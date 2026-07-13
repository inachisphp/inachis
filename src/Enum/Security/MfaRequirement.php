<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum\Security;

enum MfaRequirement: string
{
    /**
     * No multi-factor authentication required.
     */
    case NONE = 'none';

    /**
     * Any configured MFA method is acceptable.
     */
    case ANY = 'any';

    /**
     * Time-based One Time Password (TOTP) is required.
     */
    case TOTP = 'totp';

    /**
     * WebAuthn (passkey/security key) is required.
     */
    case WEBAUTHN = 'webauthn';
}
