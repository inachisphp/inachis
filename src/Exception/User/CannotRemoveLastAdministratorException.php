<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Exception\User;

use RuntimeException;

/**
 * Thrown when an operation would remove the last active Administrator.
 */
final class CannotRemoveLastAdministratorException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'You cannot remove, disable, or revoke the Administrator role from the last active Administrator.'
        );
    }
}
