<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authentication;

use Inachis\Entity\User\User;
use Inachis\Security\Policy\SecurityPolicyManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(
        // private readonly SecurityPolicyManager $securityPolicyManager,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isEnabled()) {
            throw new CustomUserMessageAccountStatusException('Your account has been disabled.');
        }

        if ($user->hasBeenRemoved()) {
            throw new CustomUserMessageAccountStatusException('Invalid credentials.');
        }
    }

    /**
     * Performs post-authentication checks.
     */
    public function checkPostAuth(
        UserInterface $user,
        ?TokenInterface $token = null,
    ): void {
        if (!$user instanceof User) {
            return;
        }

        // if ($this->securityPolicyManager->isPasswordExpired($user)) {
        //     throw new CustomUserMessageAccountStatusException(
        //         'Your password has expired. Please reset it before signing in.'
        //     );
        // }
    }
}
