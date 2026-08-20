<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\User;

interface UserProtectionServiceInterface
{
    public function assertAdministratorCanBeRemoved(): void;

    public function assertAdministratorsCanBeRemoved(iterable $users): void;
}
