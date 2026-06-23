<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity\Security;

use Inachis\Entity\Security\SecurityPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(SecurityPolicy::class)]
class SecurityPolicyTest extends TestCase
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
        self::assertSame(12, $this->policy->getMinLength());
        self::assertTrue($this->policy->getRequireUppercase());
        self::assertTrue($this->policy->getRequireLowercase());
        self::assertTrue($this->policy->getRequireNumber());
        self::assertTrue($this->policy->getRequireSpecial());
        self::assertNull($this->policy->getPasswordRegex());
        self::assertNull($this->policy->getPasswordExpiryDays());
        self::assertSame(5, $this->policy->getPasswordHistory());
        self::assertSame(5, $this->policy->getMaxFailedLoginAttempts());
        self::assertSame(15, $this->policy->getLockoutDurationMinutes());
        self::assertFalse($this->policy->getAdminRequire2FA());
        self::assertFalse($this->policy->getSuperAdminRequire2FA());
        self::assertFalse($this->policy->getSuperAdminRequiresWebAuthn());
        self::assertTrue($this->policy->getStepUpForSensitiveActions());
        self::assertFalse($this->policy->getIsReadOnly());
        self::assertFalse($this->policy->getIsActive());
        self::assertInstanceOf(\DateTimeImmutable::class, $this->policy->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $this->policy->getUpdatedAt());
    }

    #[Test]
    public function setAndGetId(): void
    {
        $uuid = Uuid::uuid4();
        $result = $this->policy->setId($uuid);
        self::assertSame($uuid, $this->policy->getId());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetName(): void
    {
        $result = $this->policy->setName('Strict Policy');
        self::assertSame('Strict Policy', $this->policy->getName());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetMinLengthValid(): void
    {
        $result = $this->policy->setMinLength(8);
        self::assertSame(8, $this->policy->getMinLength());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setMinLengthThrowsExceptionOnInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password minimum length must be at least 1.');
        $this->policy->setMinLength(0);
    }

    #[Test]
    public function setAndGetRequireUppercase(): void
    {
        $result = $this->policy->setRequireUppercase(false);
        self::assertFalse($this->policy->getRequireUppercase());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetRequireLowercase(): void
    {
        $result = $this->policy->setRequireLowercase(false);
        self::assertFalse($this->policy->getRequireLowercase());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetRequireNumber(): void
    {
        $result = $this->policy->setRequireNumber(false);
        self::assertFalse($this->policy->getRequireNumber());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetRequireSpecial(): void
    {
        $result = $this->policy->setRequireSpecial(false);
        self::assertFalse($this->policy->getRequireSpecial());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetPasswordRegexValid(): void
    {
        $result = $this->policy->setPasswordRegex('/^[a-zA-Z0-9]+$/');
        self::assertSame('/^[a-zA-Z0-9]+$/', $this->policy->getPasswordRegex());
        self::assertSame($this->policy, $result);

        $this->policy->setPasswordRegex(null);
        self::assertNull($this->policy->getPasswordRegex());
    }

    #[Test]
    public function setPasswordRegexThrowsExceptionOnInvalidRegex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern provided.');
        $this->policy->setPasswordRegex('invalid_regex_pattern');
    }

    #[Test]
    public function setAndGetPasswordExpiryDaysValid(): void
    {
        $result = $this->policy->setPasswordExpiryDays(90);
        self::assertSame(90, $this->policy->getPasswordExpiryDays());
        self::assertSame($this->policy, $result);

        $this->policy->setPasswordExpiryDays(null);
        self::assertNull($this->policy->getPasswordExpiryDays());
    }

    #[Test]
    public function setPasswordExpiryDaysThrowsExceptionOnInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password expiry must be positive or null.');
        $this->policy->setPasswordExpiryDays(0);
    }

    #[Test]
    public function setAndGetPasswordHistoryValid(): void
    {
        $result = $this->policy->setPasswordHistory(0);
        self::assertSame(0, $this->policy->getPasswordHistory());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setPasswordHistoryThrowsExceptionOnInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password history must be zero or positive.');
        $this->policy->setPasswordHistory(-1);
    }

    #[Test]
    public function setAndGetMaxFailedLoginAttemptsValid(): void
    {
        $result = $this->policy->setMaxFailedLoginAttempts(3);
        self::assertSame(3, $this->policy->getMaxFailedLoginAttempts());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setMaxFailedLoginAttemptsThrowsExceptionOnInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Max failed login attempts must be at least 1.');
        $this->policy->setMaxFailedLoginAttempts(0);
    }

    #[Test]
    public function setAndGetLockoutDurationMinutesValid(): void
    {
        $result = $this->policy->setLockoutDurationMinutes(30);
        self::assertSame(30, $this->policy->getLockoutDurationMinutes());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setLockoutDurationMinutesThrowsExceptionOnInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Lockout duration must be at least 1 minute.');
        $this->policy->setLockoutDurationMinutes(0);
    }

    #[Test]
    public function setAndGetAdminRequire2FA(): void
    {
        $result = $this->policy->setAdminRequire2FA(true);
        self::assertTrue($this->policy->getAdminRequire2FA());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetSuperAdminRequire2FA(): void
    {
        $result = $this->policy->setSuperAdminRequire2FA(true);
        self::assertTrue($this->policy->getSuperAdminRequire2FA());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetSuperAdminRequiresWebAuthn(): void
    {
        $result = $this->policy->setSuperAdminRequiresWebAuthn(true);
        self::assertTrue($this->policy->getSuperAdminRequiresWebAuthn());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetStepUpForSensitiveActions(): void
    {
        $result = $this->policy->setStepUpForSensitiveActions(false);
        self::assertFalse($this->policy->getStepUpForSensitiveActions());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetIsReadOnly(): void
    {
        $result = $this->policy->setIsReadOnly(true);
        self::assertTrue($this->policy->getIsReadOnly());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function setAndGetIsActive(): void
    {
        $result = $this->policy->setIsActive(true);
        self::assertTrue($this->policy->getIsActive());
        self::assertSame($this->policy, $result);
    }

    #[Test]
    public function doctrineLifecycleCallbacks(): void
    {
        $reflection = new \ReflectionClass(SecurityPolicy::class);
        
        $prePersist = $reflection->getMethod('onPrePersist');
        $prePersist->invoke($this->policy);
        
        $preUpdate = $reflection->getMethod('onPreUpdate');
        $preUpdate->invoke($this->policy);

        self::assertInstanceOf(\DateTimeImmutable::class, $this->policy->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $this->policy->getUpdatedAt());
    }
}
