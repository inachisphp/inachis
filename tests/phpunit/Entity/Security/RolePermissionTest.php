<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Security;

use Inachis\Entity\Security\Role;
use Inachis\Entity\Security\RolePermission;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(RolePermission::class)]
class RolePermissionTest extends TestCase
{
    private RolePermission $permission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permission = new RolePermission();
    }

    #[Test]
    public function defaultConstructorProducesEmptyValues(): void
    {
        self::assertNull($this->permission->getId());
    }

    #[Test]
    public function setAndGetAction(): void
    {
        $result = $this->permission->setAction('CREATE');
        self::assertSame('CREATE', $this->permission->getAction());
        self::assertSame($this->permission, $result);
    }

    #[Test]
    public function setAndGetResource(): void
    {
        $result = $this->permission->setResource('PAGE');
        self::assertSame('PAGE', $this->permission->getResource());
        self::assertSame($this->permission, $result);
    }

    #[Test]
    public function setAndGetRole(): void
    {
        $role = new Role();
        $result = $this->permission->setRole($role);
        self::assertSame($role, $this->permission->getRole());
        self::assertSame($this->permission, $result);
    }

    #[Test]
    public function testSetIdViaReflection(): void
    {
        $uuid = Uuid::uuid4();
        $reflection = new \ReflectionClass(RolePermission::class);
        $property = $reflection->getProperty('id');
        $property->setValue($this->permission, $uuid);

        self::assertSame($uuid, $this->permission->getId());
    }
}
