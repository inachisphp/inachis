<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * ValidIPAddressValidator class
 */
class ValidIPAddressValidator extends ConstraintValidator
{
    /**
     * Validate the IP address
     *
     * @param mixed $value
     * @param Constraint $constraint
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
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
     * Validate IPv4 address
     *
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
     * Validate IPv6 address
     *
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
