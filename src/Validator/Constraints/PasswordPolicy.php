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
 * Constraint for password policy
 */
#[\Attribute]
class PasswordPolicy extends Constraint
{
    public string $message = 'The password does not meet the security policy requirements.';
}
