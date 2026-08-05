<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Security;

use Inachis\Entity\Security\SecurityPolicy;
use Inachis\Enum\Security\AuthenticationPolicy;
use Inachis\Enum\Security\PasswordStrengthLevel;
use Inachis\Enum\Security\SensitiveAction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(SecurityPolicy::class)]
final class SecurityPolicyTest extends TestCase
{
    private SecurityPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new SecurityPolicy();
    }

    #[Test]
    public function defaultConstructorProducesExpectedValues(): void
    {
        self::assertNull($this->policy->getId());

        self::assertSame('', $this->policy->getName());
        self::assertSame('', $this->policy->getIdentifier());
        self::assertNull($this->policy->getDescription());

        self::assertSame(1, $this->policy->getVersion());

        self::assertSame(14, $this->policy->getMinimumPasswordLength());
        self::assertNull($this->policy->getMaximumPasswordLength());
        self::assertFalse($this->policy->hasMaximumPasswordLength());

        self::assertSame(
            PasswordStrengthLevel::STANDARD,
            $this->policy->getPasswordStrength(),
        );

        self::assertTrue($this->policy->getRejectCompromisedPasswords());

        self::assertSame(5, $this->policy->getPasswordReuseLimit());
        self::assertTrue($this->policy->hasPasswordHistory());

        self::assertNull($this->policy->getMinimumPasswordAgeDays());

        self::assertNull($this->policy->getPasswordLifetimeDays());
        self::assertFalse($this->policy->hasPasswordExpiry());

        self::assertSame(
            AuthenticationPolicy::MFA_REQUIRED,
            $this->policy->getAdministratorPolicy(),
        );

        self::assertSame(
            AuthenticationPolicy::WEBAUTHN_REQUIRED,
            $this->policy->getSuperAdministratorPolicy(),
        );

        self::assertTrue($this->policy->getRequireStepUpAuthentication());

        self::assertCount(3, $this->policy->getStepUpRequiredActions());

        self::assertFalse($this->policy->isReadOnly());
        self::assertFalse($this->policy->isActive());

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $this->policy->getCreatedAt(),
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $this->policy->getUpdatedAt(),
        );
    }

    #[Test]
    public function identifierCanBeSet(): void
    {
        $result = $this->policy->setIdentifier('  My_Policy  ');

        self::assertSame('my_policy', $this->policy->getIdentifier());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function nameCanBeSet(): void
    {
        $result = $this->policy->setName('  Strict Policy  ');

        self::assertSame('Strict Policy', $this->policy->getName());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function descriptionCanBeSet(): void
    {
        $this->policy->setDescription('  Description  ');
        self::assertSame('Description', $this->policy->getDescription());

        $this->policy->setDescription(null);
        self::assertNull($this->policy->getDescription());
    }

    #[Test]
    public function versionCanBeIncremented(): void
    {
        self::assertSame(1, $this->policy->getVersion());

        $result = $this->policy->incrementVersion();

        self::assertSame(2, $this->policy->getVersion());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function minimumPasswordLengthCanBeChanged(): void
    {
        $result = $this->policy->setMinimumPasswordLength(20);

        self::assertSame(20, $this->policy->getMinimumPasswordLength());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function minimumPasswordLengthMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->policy->setMinimumPasswordLength(0);
    }

    #[Test]
    public function maximumPasswordLengthCanBeChanged(): void
    {
        $result = $this->policy->setMaximumPasswordLength(128);

        self::assertSame(128, $this->policy->getMaximumPasswordLength());
        self::assertTrue($this->policy->hasMaximumPasswordLength());
        self::assertSame($this->policy, $result);

        $this->policy->setMaximumPasswordLength(null);

        self::assertNull($this->policy->getMaximumPasswordLength());
        self::assertFalse($this->policy->hasMaximumPasswordLength());
    }

    #[Test]
    public function maximumPasswordLengthCannotBeLessThanMinimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->policy->setMaximumPasswordLength(10);
    }

    #[Test]
    public function passwordStrengthCanBeChanged(): void
    {
        $result = $this->policy->setPasswordStrength(
            PasswordStrengthLevel::VERY_STRONG,
        );

        self::assertSame(
            PasswordStrengthLevel::VERY_STRONG,
            $this->policy->getPasswordStrength(),
        );

        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function compromisedPasswordCheckingCanBeChanged(): void
    {
        $result = $this->policy->setRejectCompromisedPasswords(false);

        self::assertFalse($this->policy->getRejectCompromisedPasswords());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function passwordReuseLimitCanBeChanged(): void
    {
        $result = $this->policy->setPasswordReuseLimit(0);

        self::assertSame(0, $this->policy->getPasswordReuseLimit());
        self::assertFalse($this->policy->hasPasswordHistory());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function passwordReuseLimitCannotBeNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->policy->setPasswordReuseLimit(-1);
    }

    #[Test]
    public function minimumPasswordAgeCanBeChanged(): void
    {
        $result = $this->policy->setMinimumPasswordAgeDays(7);

        self::assertSame(7, $this->policy->getMinimumPasswordAgeDays());
        self::assertSame($this->policy, $result);

        $this->policy->setMinimumPasswordAgeDays(null);

        self::assertNull($this->policy->getMinimumPasswordAgeDays());
    }

    #[Test]
    public function passwordLifetimeCanBeChanged(): void
    {
        $result = $this->policy->setPasswordLifetimeDays(90);

        self::assertSame(90, $this->policy->getPasswordLifetimeDays());
        self::assertTrue($this->policy->hasPasswordExpiry());
        self::assertSame($this->policy, $result);

        $this->policy->setPasswordLifetimeDays(null);

        self::assertNull($this->policy->getPasswordLifetimeDays());
        self::assertFalse($this->policy->hasPasswordExpiry());
    }

    #[Test]
    public function administratorPolicyCanBeChanged(): void
    {
        $result = $this->policy->setAdministratorPolicy(
            AuthenticationPolicy::TOTP_REQUIRED,
        );

        self::assertSame(
            AuthenticationPolicy::TOTP_REQUIRED,
            $this->policy->getAdministratorPolicy(),
        );

        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function superAdministratorPolicyCanBeChanged(): void
    {
        $result = $this->policy->setSuperAdministratorPolicy(
            AuthenticationPolicy::MFA_REQUIRED,
        );

        self::assertSame(
            AuthenticationPolicy::MFA_REQUIRED,
            $this->policy->getSuperAdministratorPolicy(),
        );

        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function requireStepUpAuthenticationCanBeChanged(): void
    {
        $result = $this->policy->setRequireStepUpAuthentication(false);

        self::assertFalse($this->policy->getRequireStepUpAuthentication());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function stepUpActionsCanBeManaged(): void
    {
        $this->policy->clearStepUpRequiredActions();
        self::assertCount(0, $this->policy->getStepUpRequiredActions());

        $this->policy->addStepUpRequiredAction(
            SensitiveAction::ROLE_MANAGEMENT,
        );

        self::assertTrue(
            $this->policy->requiresStepUpFor(
                SensitiveAction::ROLE_MANAGEMENT,
            ),
        );

        $this->policy->removeStepUpRequiredAction(
            SensitiveAction::ROLE_MANAGEMENT,
        );

        self::assertFalse(
            $this->policy->requiresStepUpFor(
                SensitiveAction::ROLE_MANAGEMENT,
            ),
        );

        $this->policy->setStepUpRequiredActions([
            SensitiveAction::MFA_RESET,
        ]);

        self::assertSame(
            [SensitiveAction::MFA_RESET],
            $this->policy->getStepUpRequiredActions(),
        );
    }

    #[Test]
    public function readOnlyCanBeChanged(): void
    {
        $result = $this->policy->setReadOnly(true);

        self::assertTrue($this->policy->isReadOnly());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function activeCanBeChanged(): void
    {
        $result = $this->policy->setActive(true);

        self::assertTrue($this->policy->isActive());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function lifecycleCallbacksUpdateTimestamps(): void
    {
        $reflection = new \ReflectionClass(SecurityPolicy::class);

        $persist = $reflection->getMethod('initialiseTimestamp');
        $persist->invoke($this->policy);

        $update = $reflection->getMethod('updateTimestamp');
        $update->invoke($this->policy);

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $this->policy->getCreatedAt(),
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $this->policy->getUpdatedAt(),
        );
    }

    #[Test]
    public function identifierCanBeAssignedViaReflection(): void
    {
        $uuid = Uuid::uuid4();

        $reflection = new \ReflectionClass(SecurityPolicy::class);
        $property = $reflection->getProperty('id');
        $property->setValue($this->policy, $uuid);

        self::assertSame($uuid, $this->policy->getId());
    }
}
