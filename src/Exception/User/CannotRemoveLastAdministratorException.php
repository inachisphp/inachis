<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Exception\User;

/**
 * Thrown when an operation would remove the last active Administrator.
 */
final class CannotRemoveLastAdministratorException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'You cannot remove, disable, or revoke the Administrator role from the last active Administrator.',
        );
    }
}
