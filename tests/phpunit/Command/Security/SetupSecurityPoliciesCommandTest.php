<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Command\Security\SetupSecurityPoliciesCommand;
use Inachis\Entity\Security\SecurityPolicy;
use Inachis\Enum\Security\AuthenticationPolicy;
use Inachis\Enum\Security\PasswordStrengthLevel;
use Inachis\Enum\Security\SensitiveAction;
use Inachis\Repository\Security\SecurityPolicyRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class SetupSecurityPoliciesCommandTest extends TestCase
{
    #[Test]
    public function itCreatesDefaultPolicies(): void
    {
        $repository = $this->createMock(SecurityPolicyRepository::class);

        $repository
            ->expects(self::exactly(3))
            ->method('findOneBy')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(3))
            ->method('persist')
            ->with(self::isInstanceOf(SecurityPolicy::class));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupSecurityPoliciesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);
    }

    #[Test]
    public function itCreatesDefaultPolicyWithExpectedConfiguration(): void
    {
        $policies = [];

        $repository = $this->createMock(SecurityPolicyRepository::class);

        $repository
            ->expects(self::exactly(3))
            ->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) use (&$policies): ?SecurityPolicy {
                    $policy = new SecurityPolicy();

                    $policies[$criteria['identifier']] = $policy;

                    return null;
                },
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(3))
            ->method('persist')
            ->willReturnCallback(
                static function (SecurityPolicy $policy) use (&$policies): void {
                    $policies[$policy->getIdentifier()] = $policy;
                },
            );

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupSecurityPoliciesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);

        self::assertArrayHasKey('default', $policies);

        $default = $policies['default'];

        self::assertSame('Default', $default->getName());
        self::assertSame(
            'default',
            $default->getIdentifier(),
        );
        self::assertSame(
            'Recommended security policy for most installations.',
            $default->getDescription(),
        );
        self::assertTrue($default->isReadOnly());
        self::assertTrue($default->isActive());
        self::assertSame(
            14,
            $default->getMinimumPasswordLength(),
        );
        self::assertNull(
            $default->getMaximumPasswordLength(),
        );
        self::assertSame(
            PasswordStrengthLevel::STANDARD,
            $default->getPasswordStrength(),
        );
        self::assertTrue(
            $default->getRejectCompromisedPasswords(),
        );
        self::assertSame(
            5,
            $default->getPasswordReuseLimit(),
        );
        self::assertNull(
            $default->getMinimumPasswordAgeDays(),
        );
        self::assertNull(
            $default->getPasswordLifetimeDays(),
        );
        self::assertSame(
            AuthenticationPolicy::MFA_REQUIRED,
            $default->getAdministratorPolicy(),
        );
        self::assertSame(
            AuthenticationPolicy::WEBAUTHN_REQUIRED,
            $default->getSuperAdministratorPolicy(),
        );
        self::assertTrue(
            $default->getRequireStepUpAuthentication(),
        );

        self::assertSame(
            [
                SensitiveAction::ROLE_MANAGEMENT,
                SensitiveAction::SECURITY_CONFIGURATION_CHANGE,
                SensitiveAction::MFA_RESET,
            ],
            $default->getStepUpRequiredActions(),
        );
    }

    #[Test]
    public function itCreatesStrictPolicyWithAllSensitiveActions(): void
    {
        $policies = [];

        $repository = $this->createMock(SecurityPolicyRepository::class);

        $repository
            ->expects(self::exactly(3))
            ->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) use (&$policies): ?SecurityPolicy {
                    $policy = new SecurityPolicy();

                    $policies[$criteria['identifier']] = $policy;

                    return null;
                },
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(3))
            ->method('persist')
            ->willReturnCallback(
                static function (SecurityPolicy $policy) use (&$policies): void {
                    $policies[$policy->getIdentifier()] = $policy;
                },
            );

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupSecurityPoliciesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);

        self::assertArrayHasKey('strict', $policies);

        $strict = $policies['strict'];

        self::assertSame('Strict', $strict->getName());
        self::assertSame(
            'strict',
            $strict->getIdentifier(),
        );
        self::assertTrue($strict->isReadOnly());
        self::assertFalse($strict->isActive());
        self::assertSame(
            18,
            $strict->getMinimumPasswordLength(),
        );
        self::assertNull(
            $strict->getMaximumPasswordLength(),
        );
        self::assertSame(
            PasswordStrengthLevel::VERY_STRONG,
            $strict->getPasswordStrength(),
        );
        self::assertTrue(
            $strict->getRejectCompromisedPasswords(),
        );
        self::assertSame(
            12,
            $strict->getPasswordReuseLimit(),
        );
        self::assertSame(
            1,
            $strict->getMinimumPasswordAgeDays(),
        );
        self::assertNull(
            $strict->getPasswordLifetimeDays(),
        );
        self::assertSame(
            AuthenticationPolicy::WEBAUTHN_REQUIRED,
            $strict->getAdministratorPolicy(),
        );
        self::assertSame(
            AuthenticationPolicy::WEBAUTHN_REQUIRED,
            $strict->getSuperAdministratorPolicy(),
        );
        self::assertTrue(
            $strict->getRequireStepUpAuthentication(),
        );

        self::assertSame(
            SensitiveAction::cases(),
            $strict->getStepUpRequiredActions(),
        );
    }

    #[Test]
    public function itCreatesCustomPolicyAsEditable(): void
    {
        $policies = [];

        $repository = $this->createMock(SecurityPolicyRepository::class);

        $repository
            ->expects(self::exactly(3))
            ->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) use (&$policies): ?SecurityPolicy {
                    $policy = new SecurityPolicy();

                    $policies[$criteria['identifier']] = $policy;

                    return null;
                },
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(3))
            ->method('persist')
            ->willReturnCallback(
                static function (SecurityPolicy $policy) use (&$policies): void {
                    $policies[$policy->getIdentifier()] = $policy;
                },
            );

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupSecurityPoliciesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);

        self::assertArrayHasKey('custom', $policies);

        $custom = $policies['custom'];

        self::assertSame('Custom', $custom->getName());
        self::assertSame(
            'custom',
            $custom->getIdentifier(),
        );
        self::assertFalse($custom->isReadOnly());
        self::assertFalse($custom->isActive());
        self::assertSame(
            14,
            $custom->getMinimumPasswordLength(),
        );
        self::assertSame(
            PasswordStrengthLevel::STANDARD,
            $custom->getPasswordStrength(),
        );
    }

    #[Test]
    public function itUpdatesExistingPolicies(): void
    {
        $existingDefault = (new SecurityPolicy())
            ->setName('Old Default')
            ->setIdentifier('default')
            ->setDescription('Old description')
            ->setReadOnly(false)
            ->setActive(false)
            ->setMinimumPasswordLength(8)
            ->setMaximumPasswordLength(100)
            ->setPasswordStrength(PasswordStrengthLevel::STANDARD)
            ->setRejectCompromisedPasswords(false)
            ->setPasswordReuseLimit(0)
            ->setMinimumPasswordAgeDays(null)
            ->setPasswordLifetimeDays(null)
            ->setAdministratorPolicy(AuthenticationPolicy::PASSWORD_REQUIRED)
            ->setSuperAdministratorPolicy(AuthenticationPolicy::PASSWORD_REQUIRED)
            ->setRequireStepUpAuthentication(false)
            ->setStepUpRequiredActions([]);

        $existingStrict = (new SecurityPolicy())
            ->setName('Old Strict')
            ->setIdentifier('strict');

        $existingCustom = (new SecurityPolicy())
            ->setName('Old Custom')
            ->setIdentifier('custom');

        $repository = $this->createMock(SecurityPolicyRepository::class);

        $repository
            ->expects(self::exactly(3))
            ->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) use (
                    $existingDefault,
                    $existingStrict,
                    $existingCustom,
                ): ?SecurityPolicy {
                    return match ($criteria['identifier']) {
                        'default' => $existingDefault,
                        'strict' => $existingStrict,
                        'custom' => $existingCustom,
                    };
                },
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(3))
            ->method('persist')
            ->with(self::isInstanceOf(SecurityPolicy::class));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupSecurityPoliciesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);

        self::assertSame(
            'Default',
            $existingDefault->getName(),
        );
        self::assertTrue($existingDefault->isReadOnly());
        self::assertTrue($existingDefault->isActive());
        self::assertSame(
            14,
            $existingDefault->getMinimumPasswordLength(),
        );
        self::assertSame(
            PasswordStrengthLevel::STANDARD,
            $existingDefault->getPasswordStrength(),
        );
        self::assertTrue(
            $existingDefault->getRejectCompromisedPasswords(),
        );
        self::assertSame(
            5,
            $existingDefault->getPasswordReuseLimit(),
        );
    }

    #[Test]
    public function itReplacesExistingStepUpActions(): void
    {
        $existing = (new SecurityPolicy())
            ->setName('Default')
            ->setIdentifier('default')
            ->setStepUpRequiredActions([
                SensitiveAction::MFA_RESET,
            ]);

        $repository = $this->createMock(SecurityPolicyRepository::class);

        $repository
            ->expects(self::exactly(3))
            ->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) use ($existing): ?SecurityPolicy {
                    return match ($criteria['identifier']) {
                        'default' => $existing,
                        'strict', 'custom' => null,
                    };
                },
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(3))
            ->method('persist')
            ->with(self::isInstanceOf(SecurityPolicy::class));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupSecurityPoliciesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);

        self::assertSame(
            [
                SensitiveAction::ROLE_MANAGEMENT,
                SensitiveAction::SECURITY_CONFIGURATION_CHANGE,
                SensitiveAction::MFA_RESET,
            ],
            $existing->getStepUpRequiredActions(),
        );
    }

    #[Test]
    public function itResetsExistingPoliciesBeforeCreatingDefaults(): void
    {
        $existingPolicies = [
            new SecurityPolicy(),
            new SecurityPolicy(),
        ];

        $repository = $this->createMock(SecurityPolicyRepository::class);

        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn($existingPolicies);

        $repository
            ->expects(self::exactly(3))
            ->method('findOneBy')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $removedPolicies = [];

        $entityManager
            ->expects(self::exactly(2))
            ->method('remove')
            ->with(self::isInstanceOf(SecurityPolicy::class))
            ->willReturnCallback(
                static function (SecurityPolicy $policy) use (&$removedPolicies): void {
                    $removedPolicies[] = $policy;
                },
            );

        $entityManager
            ->expects(self::exactly(2))
            ->method('flush');

        $entityManager
            ->expects(self::exactly(3))
            ->method('persist')
            ->with(self::isInstanceOf(SecurityPolicy::class));

        $command = new SetupSecurityPoliciesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput(['--reset' => true]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);

        self::assertCount(2, $removedPolicies);
        self::assertSame($existingPolicies[0], $removedPolicies[0]);
        self::assertSame($existingPolicies[1], $removedPolicies[1]);
    }
}
