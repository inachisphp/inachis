<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\User;

interface UserProtectionServiceInterface
{
    /**
     * Determines if the {@link User} can be deleted, disabled, or the
     * role removed.
     */
    public function assertAdministratorCanBeRemoved(): void;

    /**
     * Determines if the {@link User}s can be deleted, disabled, or a
     * role removed from them.
     * 
     * @param iterable<\Inachis\Entity\User\User> $users
     */
    public function assertAdministratorsCanBeRemoved(iterable $users): void;
}
