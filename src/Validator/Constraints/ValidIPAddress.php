<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidIPAddress extends Constraint
{
    public $message = '"{{ string }}" is not a valid IPv4 or IPv6 address';
}
