<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation message for invalid IP addresses.
 */
class ValidIPAddress extends Constraint
{
    public string $message = '"{{ string }}" is not a valid IPv4 or IPv6 address';
}
