<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Security\Authentication;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\MfaRequirement;

/**
 * Checks TOTP requirements for {@link Role} and {@link User}
 */
class TotpRequirementChecker
{
    /**
     * Check if user's {@link Role} requires TOTP
     *
     * @param User $user
     * @return bool
     */
    public function requiresTotp(User $user): bool
    {
        foreach ($user->getAssignedRoles() as $role) {
            if ($role->getMfaRequirement() == MfaRequirement::TOTP) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if User has TOTP enabled
     *
     * @param User $user
     * @return bool
     */
    public function hasEnabledTotp(User $user): bool
    {
        $totp = $user->getTotp();

        return $totp !== null
            && $totp->getEnabledAt() !== null;
    }

    /**
     * Check if user requires TOTP setting up
     *
     * @param User $user
     * @return bool
     */
    public function needsSetup(User $user): bool
    {
        return $this->requiresTotp($user)
            && !$this->hasEnabledTotp($user);
    }
}
