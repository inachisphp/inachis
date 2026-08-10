<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\User;

use Inachis\Entity\User\User;
use Inachis\Exception\User\CannotRemoveLastAdministratorException;
use Inachis\Repository\User\UserRepository;
use Inachis\Service\User\UserProtectionService;
use PHPUnit\Framework\TestCase;

class UserProtectionServiceTest extends TestCase
{
    public function testAssertAdministratorCanBeRemovedThrowsExceptionWhenOnlyOneAdminExists(): void
    {
        $userRepository = $this->createUserRepository(1);
        $service = new UserProtectionService($userRepository);

        $this->expectException(CannotRemoveLastAdministratorException::class);

        $service->assertAdministratorCanBeRemoved();
    }

    public function testAssertAdministratorCanBeRemovedThrowsExceptionWhenNoAdminsExist(): void
    {
        $userRepository = $this->createUserRepository(0);
        $service = new UserProtectionService($userRepository);

        $this->expectException(CannotRemoveLastAdministratorException::class);

        $service->assertAdministratorCanBeRemoved();
    }

    public function testAssertAdministratorCanBeRemovedSucceedsWhenMultipleAdminsExist(): void
    {
        $userRepository = $this->createUserRepository(2);
        $service = new UserProtectionService($userRepository);

        $service->assertAdministratorCanBeRemoved();

        $this->assertTrue(true);
    }

    public function testAssertAdministratorsCanBeRemovedThrowsExceptionWhenRemainingAdminsIsLessThanOne(): void
    {
        $userRepository = $this->createUserRepository(2);
        $service = new UserProtectionService($userRepository);

        $admin1 = $this->createUser(isAdmin: true, isEnabled: true, isRemoved: false);
        $admin2 = $this->createUser(isAdmin: true, isEnabled: true, isRemoved: false);

        $this->expectException(CannotRemoveLastAdministratorException::class);

        $service->assertAdministratorsCanBeRemoved([$admin1, $admin2]);
    }

    public function testAssertAdministratorsCanBeRemovedSucceedsWhenAtLeastOneAdminRemains(): void
    {
        $userRepository = $this->createUserRepository(3);
        $service = new UserProtectionService($userRepository);

        $admin1 = $this->createUser(isAdmin: true, isEnabled: true, isRemoved: false);
        $admin2 = $this->createUser(isAdmin: true, isEnabled: true, isRemoved: false);

        $service->assertAdministratorsCanBeRemoved([$admin1, $admin2]);

        $this->assertTrue(true);
    }

    public function testAssertAdministratorsCanBeRemovedIgnoresNonAdminDisabledOrRemovedUsers(): void
    {
        $userRepository = $this->createUserRepository(2);
        $service = new UserProtectionService($userRepository);

        $nonAdmin = $this->createUser(isAdmin: false, isEnabled: true, isRemoved: false);
        $disabledAdmin = $this->createUser(isAdmin: true, isEnabled: false, isRemoved: false);
        $removedAdmin = $this->createUser(isAdmin: true, isEnabled: true, isRemoved: true);

        // None of these users count as active administrators being removed, so remaining stays at 2
        $service->assertAdministratorsCanBeRemoved([$nonAdmin, $disabledAdmin, $removedAdmin]);

        $this->assertTrue(true);
    }

    private function createUserRepository(int $activeAdminsCount): UserRepository
    {
        $reflection = new \ReflectionClass(UserRepository::class);

        if (!$reflection->isFinal()) {
            $repository = $this->createStub(UserRepository::class);
            $repository->method('countActiveAdministrators')->willReturn($activeAdminsCount);

            return $repository;
        }

        return $reflection->newInstanceWithoutConstructor();
    }

    private function createUser(bool $isAdmin, bool $isEnabled, bool $isRemoved): User
    {
        $reflection = new \ReflectionClass(User::class);

        if (!$reflection->isFinal()) {
            $user = $this->createStub(User::class);
            $user->method('isAdministrator')->willReturn($isAdmin);
            $user->method('isEnabled')->willReturn($isEnabled);
            $user->method('hasBeenRemoved')->willReturn($isRemoved);

            return $user;
        }

        $user = $reflection->newInstanceWithoutConstructor();

        foreach (['administrator', 'isAdmin', 'isAdministrator'] as $propName) {
            if ($reflection->hasProperty($propName)) {
                $prop = $reflection->getProperty($propName);
                $prop->setValue($user, $isAdmin);
            }
        }

        foreach (['enabled', 'isEnabled', 'active'] as $propName) {
            if ($reflection->hasProperty($propName)) {
                $prop = $reflection->getProperty($propName);
                $prop->setValue($user, $isEnabled);
            }
        }

        foreach (['removed', 'isRemoved', 'deleted'] as $propName) {
            if ($reflection->hasProperty($propName)) {
                $prop = $reflection->getProperty($propName);
                $prop->setValue($user, $isRemoved);
            }
        }

        return $user;
    }
}
