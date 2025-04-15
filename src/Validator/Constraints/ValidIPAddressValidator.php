<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidIPAddressValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidIPAddress) {
            throw new UnexpectedValueException($constraint, ValidIPAddress::class);
        }
        if (null === $value || '' === $value) {
            return;
        }
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }
        if (!$this->validateIPv4($value) && !$this->validateIPv6($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }

    /**
     * @param string $ipAddress
     * @return bool
     */
    public function validateIPv4(string $ipAddress): bool
    {
        return (bool) preg_match(
            '/^(1?[0-9]{1,2}|2[0-4][0-9]|25[0-5])\.' .
            '(1?[0-9]{1,2}|2[0-4][0-9]|25[0-5])\.' .
            '(1?[0-9]{1,2}|2[0-4][0-9]|25[0-5])\.' .
            '(1?[0-9]{1,2}|2[0-4][0-9]|25[0-5])$/',
            $ipAddress
        );
    }

    /**
     * @param string $ipAddress
     * @return bool
     */
    public function validateIPv6(string $ipAddress): bool
    {
        return (bool) preg_match(
            '/^([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4}$/',
            $ipAddress
        );
    }
}
