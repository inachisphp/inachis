<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Validator\Constraints;

use Inachis\Entity\Security\SecurityPolicy;
use Inachis\Service\Security\ActiveSecurityPolicyService;
use Inachis\Validator\Constraints\PasswordPolicy;
use Inachis\Validator\PasswordPolicyValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

#[AllowMockObjectsWithoutExpectations]
class PasswordPolicyValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        $policy = new SecurityPolicy();
        $policy->setMinLength(8)
            ->setRequireUppercase(true)
            ->setRequireLowercase(true)
            ->setRequireNumber(true)
            ->setRequireSpecial(true);

        $service = $this->createStub(ActiveSecurityPolicyService::class);
        $service->method('getActivePolicy')->willReturn($policy);

        return new PasswordPolicyValidator($service);
    }

    #[Test]
    public function testValidateAcceptsPasswordMatchingActivePolicy(): void
    {
        $this->validator->validate('ValidPassword1!', new PasswordPolicy());

        $this->assertNoViolation();
    }

    #[Test]
    public function testValidateRejectsPasswordBelowActivePolicyLength(): void
    {
        $this->validator->validate('Short1!', new PasswordPolicy());

        $this->buildViolation('Your password should be at least 8 characters')
            ->buildNextViolation('Your password must be more complex. See the below guidance.')
            ->assertRaised();
    }

    #[Test]
    public function testValidateRejectsPasswordBelowRequiredStrength(): void
    {
        $this->validator->validate('password', new PasswordPolicy());

        $this->buildViolation('Your password must contain at least one uppercase letter')
            ->buildNextViolation('Your password must contain at least one number')
            ->buildNextViolation('Your password must contain at least one special character')
            ->buildNextViolation('Your password must be more complex. See the below guidance.')
            ->assertRaised();
    }
}
