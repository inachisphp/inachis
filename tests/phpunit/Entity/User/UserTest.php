<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\User;

use Inachis\Entity\Security\Role;
use Inachis\Entity\User\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid4();

        $this->user->setId($uuid);

        $this->assertSame($uuid, $this->user->getId());
    }

    public function testSetAndGetUsername(): void
    {
        $this->user->setUsername('testuser');

        $this->assertSame('testuser', $this->user->getUsername());
        $this->assertSame('testuser', $this->user->getUserIdentifier());
    }

    public function testSetAndGetPassword(): void
    {
        $now = new \DateTimeImmutable();

        $this->user->setPassword('password', $now);

        $this->assertSame('password', $this->user->getPassword());
        $this->assertSame($now, $this->user->getPasswordChangedAt());
    }

    public function testSetAndGetPlainPassword(): void
    {
        $this->user->setPlainPassword('password');

        $this->assertSame('password', $this->user->getPlainPassword());
        $this->assertNull($this->user->getPassword());
    }

    public function testSetAndGetEmail(): void
    {
        $this->user->setEmail('test@example.com');

        $this->assertSame('test@example.com', $this->user->getEmail());
    }

    public function testSetAndGetDisplayName(): void
    {
        $this->user->setDisplayName('Test User');

        $this->assertSame('Test User', $this->user->getDisplayName());
    }

    public function testGetInitials(): void
    {
        $this->user->setDisplayName('Test User');

        $this->assertSame('TU', $this->user->getInitials());

        $this->user->setDisplayName('Forename Middle Surname');

        $this->assertSame('FMS', $this->user->getInitials());

        $this->user->setDisplayName('');

        $this->assertSame('', $this->user->getInitials());
    }

    public function testSetAndGetAvatar(): void
    {
        $this->user->setAvatar('avatar.jpg');

        $this->assertSame('avatar.jpg', $this->user->getAvatar());

        $this->user->setAvatar(null);

        $this->assertNull($this->user->getAvatar());
    }

    public function testIsEnabled(): void
    {
        $this->assertTrue($this->user->isEnabled());

        $this->user->setActive(false);

        $this->assertFalse($this->user->isEnabled());
    }

    public function testHasBeenRemoved(): void
    {
        $this->assertFalse($this->user->hasBeenRemoved());

        $this->user->setRemoved(true);

        $this->assertTrue($this->user->hasBeenRemoved());
    }

    public function testLifecycleCallbacksPopulateDates(): void
    {
        $this->user->onPrePersist();

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $this->user->getCreatedAt(),
        );

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $this->user->getUpdatedAt(),
        );

        $updated = $this->user->getUpdatedAt();

        usleep(1000);

        $this->user->onPreUpdate();

        $this->assertGreaterThan(
            $updated,
            $this->user->getUpdatedAt(),
        );
    }

    public function testPasswordChangedAt(): void
    {
        $date = new \DateTimeImmutable('-1 day');

        $this->user->setPasswordChangedAt($date);

        $this->assertSame($date, $this->user->getPasswordChangedAt());
    }

    public function testLastLoginAt(): void
    {
        $date = new \DateTimeImmutable();

        $this->user->setLastLoginAt($date);

        $this->assertSame($date, $this->user->getLastLoginAt());
    }

    public function testValidateEmail(): void
    {
        $this->user->setEmail('test@test.com');
        $this->assertTrue($this->user->validateEmail());

        $this->user->setEmail('test@test.co.uk');
        $this->assertTrue($this->user->validateEmail());

        $this->user->setEmail("test.o'test@test.com");
        $this->assertTrue($this->user->validateEmail());

        $this->user->setEmail('test+something@test.com');
        $this->assertTrue($this->user->validateEmail());

        $this->user->setEmail('invalid-email');
        $this->assertFalse($this->user->validateEmail());
    }

    public function testAssignedRoles(): void
    {
        $role = new Role();
        $role->setIdentifier('administrator');

        $this->user->addAssignedRole($role);

        $this->assertCount(1, $this->user->getAssignedRoles());

        $this->assertContains('ROLE_ADMINISTRATOR', $this->user->getRoles());
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
        $this->assertContains('ROLE_USER', $this->user->getRoles());

        $this->user->removeAssignedRole($role);

        $this->assertCount(0, $this->user->getAssignedRoles());
        $this->assertSame(['ROLE_USER'], $this->user->getRoles());
    }

    public function testEraseCredentials(): void
    {
        $this->user->setPlainPassword('password');

        $this->user->eraseCredentials();

        $this->assertNull($this->user->getPlainPassword());
    }

    public function testErase(): void
    {
        $this->user
            ->setPassword('password')
            ->setEmail('test@example.com')
            ->setAvatar('avatar.jpg')
            ->setActive(true)
            ->setRemoved(false);

        $this->user->erase();

        $this->assertNull($this->user->getPassword());
        $this->assertNull($this->user->getEmail());
        $this->assertNull($this->user->getAvatar());
        $this->assertFalse($this->user->isEnabled());
        $this->assertTrue($this->user->hasBeenRemoved());
    }
}
