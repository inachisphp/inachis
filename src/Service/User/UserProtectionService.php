<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\User;

use Inachis\Exception\User\CannotRemoveLastAdministratorException;
use Inachis\Repository\User\UserRepository;

/**
 * Service for determining of a {@link User} can be removed/disabled or
 * have 'admin' {@link Role} removed based on if they are the last administrator.
 */
final readonly class UserProtectionService implements UserProtectionServiceInterface
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * Determines if the {@link User} can be deleted, disabled, or the
     * role removed.
     */
    public function assertAdministratorCanBeRemoved(): void
    {
        if ($this->userRepository->countActiveAdministrators() <= 1) {
            throw new CannotRemoveLastAdministratorException();
        }
    }

    /**
     * Determines if the {@link User}s can be deleted, disabled, or a
     * role removed from them.
     */
    public function assertAdministratorsCanBeRemoved(iterable $users): void
    {
        $remaining = $this->userRepository->countActiveAdministrators();

        foreach ($users as $user) {
            if ($user->isAdministrator() && $user->isEnabled() && !$user->hasBeenRemoved()) {
                --$remaining;
            }
        }

        if ($remaining < 1) {
            throw new CannotRemoveLastAdministratorException();
        }
    }
}
