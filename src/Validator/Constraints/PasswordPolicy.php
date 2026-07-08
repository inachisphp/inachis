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
    public string $minLengthMessage = 'Your password should be at least {{ minLength }} characters';
    public string $uppercaseMessage = 'Your password must contain at least one uppercase letter';
    public string $lowercaseMessage = 'Your password must contain at least one lowercase letter';
    public string $numberMessage = 'Your password must contain at least one number';
    public string $specialMessage = 'Your password must contain at least one special character';
    public string $strengthMessage = 'Your password must be more complex. See the below guidance.';
}
