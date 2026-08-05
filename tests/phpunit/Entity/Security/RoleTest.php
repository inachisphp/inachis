<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Security;

use Inachis\Entity\Security\Role;
use Inachis\Entity\Security\RolePermission;
use Inachis\Enum\Security\AuthenticationPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(Role::class)]
class RoleTest extends TestCase
{
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->role = new Role();
    }

    #[Test]
    public function defaultConstructorProducesEmptyValues(): void
    {
        self::assertNull($this->role->getId());
        self::assertFalse($this->role->isDisableReview());
        self::assertEmpty($this->role->getRolePermissions());
    }

    #[Test]
    public function setAndGetName(): void
    {
        $result = $this->role->setName('Admin');
        self::assertSame('Admin', $this->role->getName());
        self::assertSame($this->role, $result);
    }

    #[Test]
    public function setAndGetDescription(): void
    {
        $result = $this->role->setDescription('Administrator Role');
        self::assertSame('Administrator Role', $this->role->getDescription());
        self::assertSame($this->role, $result);
    }

    #[Test]
    public function setAndGetDisableReview(): void
    {
        $result = $this->role->setDisableReview(true);
        self::assertTrue($this->role->isDisableReview());
        self::assertSame($this->role, $result);
    }

    #[Test]
    public function testSetIdViaReflection(): void
    {
        $uuid = Uuid::uuid4();
        $reflection = new \ReflectionClass(Role::class);
        $property = $reflection->getProperty('id');
        $property->setValue($this->role, $uuid);

        self::assertSame($uuid, $this->role->getId());
    }

    #[Test]
    public function nameChangesDoNotReplaceTheInitialIdentifier(): void
    {
        $this->role->setName('Administrator');

        self::assertSame('administrator', $this->role->getIdentifier());

        $this->role->setName('Super Administrator');

        self::assertSame('administrator', $this->role->getIdentifier());
    }

    #[Test]
    public function setNameDoesNotOverwriteExplicitIdentifier(): void
    {
        $this->role
            ->setIdentifier('custom-role')
            ->setName('Administrator');

        self::assertSame('custom-role', $this->role->getIdentifier());
    }

    #[Test]
    public function manageRolePermissions(): void
    {
        $permission = new RolePermission();
        $result = $this->role->addRolePermission($permission);

        self::assertSame($this->role, $result);
        self::assertCount(1, $this->role->getRolePermissions());
        self::assertSame($permission, $this->role->getRolePermissions()->first());
        self::assertSame($this->role, $permission->getRole());

        // Re-adding shouldn't duplicate
        $this->role->addRolePermission($permission);
        self::assertCount(1, $this->role->getRolePermissions());

        $this->role->removeRolePermission($permission);
        self::assertEmpty($this->role->getRolePermissions());
    }

    #[Test]
    public function authenticationPolicyCanBeChanged(): void
    {
        foreach (AuthenticationPolicy::cases() as $policy) {
            $this->role->setAuthenticationPolicy($policy);

            self::assertSame(
                $policy,
                $this->role->getAuthenticationPolicy(),
            );
        }
    }

    #[Test]
    public function systemRoleFlagCanBeChanged(): void
    {
        self::assertFalse($this->role->isSystemRole());

        $this->role->setSystemRole(true);

        self::assertTrue($this->role->isSystemRole());
    }

    #[Test]
    public function administratorIsDetectedFromIdentifier(): void
    {
        $this->role->setIdentifier('administrator');
        self::assertTrue($this->role->isAdministrator());

        $this->role->setIdentifier('admin');
        self::assertTrue($this->role->isAdministrator());

        $this->role->setIdentifier('editor');
        self::assertFalse($this->role->isAdministrator());
    }

    #[Test]
    public function userCountDefaultsToZero(): void
    {
        self::assertSame(0, $this->role->getUserCount());
    }

    #[Test]
    public function canBeDeletedDependsOnSystemRoleAndAssignedUsers(): void
    {
        self::assertTrue($this->role->canBeDeleted());

        $this->role->setSystemRole(true);

        self::assertFalse($this->role->canBeDeleted());
    }
}
