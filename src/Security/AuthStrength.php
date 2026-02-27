<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */
namespace Inachis\Security;

use Inachis\Entity\User;

/**
 * AuthStrength class
 */
class AuthStrength
{
    public const NONE = 0;
    public const PASSWORD = 1;
    public const TOTP = 2;
    public const WEBAUTHN = 3;

    /**
     * Check if the user has strong enough authentication
     *
     * @param User $user
     * @param string $level
     * @return bool
     */
    public function isStrongEnough(User $user, string $level): bool
    {
        return match ($level) {
            'ADMIN' => $user->hasAnySecondFactor(),
            'SUPER_ADMIN' => $user->hasStrongSecondFactor(),
            'STEP_UP' => $this->hasRecentStepUp($user),
            default => true,
        };
    }

    /**
     * Check if the user has had a recent step-up
     *
     * @param User $user
     * @return bool
     */
    private function hasRecentStepUp(User $user): bool
    {
        // @todo stored in session with timestamp
        return true;
    }
}
