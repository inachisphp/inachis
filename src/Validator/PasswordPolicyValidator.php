<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Validator;

use Inachis\Entity\SecurityPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * PasswordPolicyValidator class
 */
class PasswordPolicyValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * Validate the password policy
     *
     * @param mixed $value
     * @param Constraint $constraint
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $policy = $this->entityManager->getRepository(SecurityPolicy::class)->find(1);
        if (!$policy) {
            return;
        }

        if (strlen($value) < $policy->getMinLength()) {
            $this->context->buildViolation('Minimum length is '.$policy->getMinLength())->addViolation();
        }

        if ($policy->getRequireUppercase() && !preg_match('/[A-Z]/', $value)) {
            $this->context->buildViolation('Must contain an uppercase letter')->addViolation();
        }

        if ($policy->getRequireLowercase() && !preg_match('/[a-z]/', $value)) {
            $this->context->buildViolation('Must contain a lowercase letter')->addViolation();
        }

        if ($policy->getRequireNumber() && !preg_match('/\d/', $value)) {
            $this->context->buildViolation('Must contain a number')->addViolation();
        }

        if ($policy->getRequireSpecial() && !preg_match('/[^a-zA-Z0-9]/', $value)) {
            $this->context->buildViolation('Must contain a special character')->addViolation();
        }
    }
}
