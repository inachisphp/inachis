<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authentication;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\AuthenticationPolicy;

/**
 * Checks TOTP requirements for {@link Role} and {@link User}.
 */
class TotpRequirementChecker
{
    /**
     * Check if user's {@link Role} requires TOTP.
     */
    public function requiresTotp(User $user): bool
    {
        foreach ($user->getAssignedRoles() as $role) {
            if (AuthenticationPolicy::TOTP_REQUIRED == $role->getAuthenticationPolicy()
                || AuthenticationPolicy::MFA_REQUIRED == $role->getAuthenticationPolicy()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if User has TOTP enabled.
     */
    public function hasEnabledTotp(User $user): bool
    {
        $totp = $user->getTotp();

        return null !== $totp
            && null !== $totp->getEnabledAt();
    }

    /**
     * Check if user requires TOTP setting up.
     */
    public function needsSetup(User $user): bool
    {
        return $this->requiresTotp($user)
            && !$this->hasEnabledTotp($user);
    }
}
