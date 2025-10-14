<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidTimezoneValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidTimezone) {
            throw new UnexpectedValueException($constraint, ValidTimezone::class);
        }
        if (null === $value || '' === $value) {
            return;
        }
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!in_array($value, timezone_identifiers_list())) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
