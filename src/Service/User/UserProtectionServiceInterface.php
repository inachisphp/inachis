<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\User;

interface UserProtectionServiceInterface
{
    public function assertAdministratorCanBeRemoved(): void;

    public function assertAdministratorsCanBeRemoved(iterable $users): void;
}
