<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation message for unrecognised timezones.
 */
class ValidTimezone extends Constraint
{
    public string $message = '"{{ string }}" is not a recognised timezone';
}
