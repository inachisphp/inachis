<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Security\Authentication;

use Inachis\Entity\User\User;
use Inachis\Security\Policy\SecurityPolicyManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
    public function __construct(
        // private readonly SecurityPolicyManager $securityPolicyManager,
    ) {}

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
     *
     * @param UserInterface $user
     * @param TokenInterface|null $token
     */
    public function checkPostAuth(
        UserInterface $user,
        ?TokenInterface $token = null
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
