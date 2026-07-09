<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Validator;

use Inachis\Service\Security\ActiveSecurityPolicyService;
use Inachis\Validator\Constraints\PasswordPolicy;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\PasswordStrengthValidator;

/**
 * PasswordPolicyValidator class
 */
class PasswordPolicyValidator extends ConstraintValidator
{
    public function __construct(private ActiveSecurityPolicyService $activeSecurityPolicyService) {}

    /**
     * Validate the password policy
     *
     * @param mixed $value
     * @param Constraint $constraint
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PasswordPolicy) {
            throw new UnexpectedTypeException($constraint, PasswordPolicy::class);
        }

        if (null === $value || '' === $value) {
            return;
        }
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $policy = $this->activeSecurityPolicyService->getActivePolicy();
        if (!$policy) {
            return;
        }

        if (strlen($value) < $policy->getMinLength()) {
            $this->context->buildViolation(strtr($constraint->minLengthMessage, ['{{ minLength }}' => (string) $policy->getMinLength()]))
                ->addViolation();
        }

        if ($policy->getRequireUppercase() && !preg_match('/[A-Z]/', $value)) {
            $this->context->buildViolation($constraint->uppercaseMessage)->addViolation();
        }

        if ($policy->getRequireLowercase() && !preg_match('/[a-z]/', $value)) {
            $this->context->buildViolation($constraint->lowercaseMessage)->addViolation();
        }

        if ($policy->getRequireNumber() && !preg_match('/\d/', $value)) {
            $this->context->buildViolation($constraint->numberMessage)->addViolation();
        }

        if ($policy->getRequireSpecial() && !preg_match('/[^a-zA-Z0-9]/', $value)) {
            $this->context->buildViolation($constraint->specialMessage)->addViolation();
        }

        $strength = PasswordStrengthValidator::estimateStrength($value);
        if ($strength < PasswordStrength::STRENGTH_WEAK) {
            $this->context->buildViolation($constraint->strengthMessage)->addViolation();
        }
    }
}
