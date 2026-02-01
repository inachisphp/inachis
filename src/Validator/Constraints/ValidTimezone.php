<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation message for unrecognised timezones
 */
class ValidTimezone extends Constraint
{
    public string $message = '"{{ string }}" is not a recognised timezone';
}
