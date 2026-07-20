<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum\Security;

enum LoginResultType: string
{
    case TYPE_SUCCESS = 'success';
    case TYPE_SUCCESS_PASSKEY = 'success_passkey';
    case TYPE_SUCCESS_TOTP = 'success_totp';
    case TYPE_SUCCESS_RECOVERY = 'success_recovery_code';
    case TYPE_SUCCESS_TRUSTED = 'success_trusted_device';
    case TYPE_FAILURE = 'failure';

    case TYPE_PASSWORD_RESET = 'password_reset';

    public function label(): string
    {
        return match ($this) {
            self::TYPE_SUCCESS => 'Success',
            self::TYPE_SUCCESS_PASSKEY => 'Success - Passkey',
            self::TYPE_SUCCESS_TOTP => 'Success - 2FA',
            self::TYPE_SUCCESS_RECOVERY => 'Success - Recovery Code',
            self::TYPE_SUCCESS_TRUSTED => 'Success - Trusted Device',
            self::TYPE_FAILURE => 'Failure',

            self::TYPE_PASSWORD_RESET => 'Password Reset',
        };
    }

    public function className(): string
    {
        return match ($this) {
            self::TYPE_SUCCESS => 'badge__ok',
            self::TYPE_SUCCESS_PASSKEY => 'badge__ok',
            self::TYPE_SUCCESS_TOTP => 'badge__ok',
            self::TYPE_SUCCESS_RECOVERY => 'badge__ok',
            self::TYPE_SUCCESS_TRUSTED => 'badge__ok',
            self::TYPE_FAILURE => 'badge__issue',

            self::TYPE_PASSWORD_RESET => 'badge__warning',
        };
    }
}
