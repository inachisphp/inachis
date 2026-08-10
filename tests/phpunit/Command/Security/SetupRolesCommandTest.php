<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Command\Security\SetupRolesCommand;
use Inachis\Entity\Security\Role;
use Inachis\Repository\Security\RoleRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class SetupRolesCommandTest extends TestCase
{
    #[Test]
    public function itCreatesDefaultRoles(): void
    {
        $repository = $this->createMock(RoleRepository::class);

        $repository
            ->expects(self::exactly(5))
            ->method('findOneBy')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(5))
            ->method('persist')
            ->with(self::isInstanceOf(Role::class));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupRolesCommand(
            $entityManager,
            $repository,
        );

        $output = new BufferedOutput();

        $result = $command->run(
            new ArrayInput([]),
            $output,
        );

        self::assertSame(Command::SUCCESS, $result);

        $display = $output->fetch();

        self::assertStringContainsString(
            'Creating system roles...',
            $display,
        );

        self::assertStringContainsString(
            'System roles updated.',
            $display,
        );
    }

    #[Test]
    public function itCreatesRolesWithExpectedProperties(): void
    {
        $roles = [];

        $repository = $this->createMock(RoleRepository::class);

        $repository
            ->expects(self::exactly(5))
            ->method('findOneBy')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(5))
            ->method('persist')
            ->willReturnCallback(
                static function (object $entity) use (&$roles): void {
                    self::assertInstanceOf(Role::class, $entity);

                    $roles[] = $entity;
                },
            );

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupRolesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);
        self::assertCount(5, $roles);

        $expectedRoles = [
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Full access to all functionality.',
            ],
            'editor' => [
                'name' => 'Editor',
                'description' => 'Can manage and publish all content.',
            ],
            'author' => [
                'name' => 'Author',
                'description' => 'Can create and edit content for publication.',
            ],
            'contributor' => [
                'name' => 'Contributor',
                'description' => 'Can submit draft content.',
            ],
            'reviewer' => [
                'name' => 'Read-only Reviewer',
                'description' => 'Can review content awaiting publication.',
            ],
        ];

        foreach ($roles as $role) {
            $identifier = $role->getIdentifier();

            self::assertArrayHasKey($identifier, $expectedRoles);

            self::assertSame(
                $expectedRoles[$identifier]['name'],
                $role->getName(),
            );

            self::assertSame(
                $expectedRoles[$identifier]['description'],
                $role->getDescription(),
            );

            self::assertTrue($role->isSystemRole());
            self::assertFalse($role->isDisableReview());

            self::assertNotEmpty(
                $role->getRolePermissions(),
                sprintf(
                    'Role "%s" should have permissions.',
                    $identifier,
                ),
            );
        }
    }

    #[Test]
    public function itUpdatesExistingRoles(): void
    {
        $roles = [];

        $repository = $this->createMock(RoleRepository::class);

        $repository
            ->expects(self::exactly(5))
            ->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) use (&$roles): Role {
                    $role = new Role();
                    $role->setIdentifier($criteria['slug']);
                    $role->setName('Old name');
                    $role->setDescription('Old description');
                    $role->setSystemRole(false);
                    $role->setDisableReview(true);

                    $roles[] = $role;

                    return $role;
                },
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(5))
            ->method('persist')
            ->with(self::isInstanceOf(Role::class));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupRolesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);
        self::assertCount(5, $roles);

        $expected = [
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Full access to all functionality.',
            ],
            'editor' => [
                'name' => 'Editor',
                'description' => 'Can manage and publish all content.',
            ],
            'author' => [
                'name' => 'Author',
                'description' => 'Can create and edit content for publication.',
            ],
            'contributor' => [
                'name' => 'Contributor',
                'description' => 'Can submit draft content.',
            ],
            'reviewer' => [
                'name' => 'Read-only Reviewer',
                'description' => 'Can review content awaiting publication.',
            ],
        ];

        foreach ($roles as $role) {
            $identifier = $role->getIdentifier();

            self::assertArrayHasKey($identifier, $expected);

            self::assertSame(
                $expected[$identifier]['name'],
                $role->getName(),
            );

            self::assertSame(
                $expected[$identifier]['description'],
                $role->getDescription(),
            );

            self::assertTrue($role->isSystemRole());
            self::assertFalse($role->isDisableReview());

            self::assertNotEmpty(
                $role->getRolePermissions(),
                sprintf(
                    'Role "%s" should have permissions.',
                    $identifier,
                ),
            );
        }
    }

    #[Test]
    public function itResetsExistingRolesBeforeCreatingDefaults(): void
    {
        $existingRole = new Role();
        $existingRole->setIdentifier('custom-role');
        $existingRole->setName('Custom Role');

        $repository = $this->createMock(RoleRepository::class);

        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$existingRole]);

        $repository
            ->expects(self::exactly(5))
            ->method('findOneBy')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(5))
            ->method('persist')
            ->with(self::isInstanceOf(Role::class));

        $entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($existingRole);

        $entityManager
            ->expects(self::exactly(2))
            ->method('flush');

        $command = new SetupRolesCommand(
            $entityManager,
            $repository,
        );

        $input = new ArrayInput([
            '--reset' => true,
        ]);

        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        self::assertSame(Command::SUCCESS, $result);

        $display = $output->fetch();

        self::assertStringContainsString(
            'Creating system roles...',
            $display,
        );

        self::assertStringContainsString(
            'System roles updated.',
            $display,
        );
    }

    #[Test]
    public function itRebuildsPermissionsOnExistingRoles(): void
    {
        $roles = [];

        $repository = $this->createMock(RoleRepository::class);

        $repository
            ->expects(self::exactly(5))
            ->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) use (&$roles): Role {
                    $role = new Role();
                    $role->setIdentifier($criteria['slug']);
                    $role->setName('Existing');
                    $role->setDescription('Existing description');

                    $roles[] = $role;

                    return $role;
                },
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::exactly(5))
            ->method('persist')
            ->with(self::isInstanceOf(Role::class));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new SetupRolesCommand(
            $entityManager,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);
        self::assertCount(5, $roles);

        foreach ($roles as $role) {
            self::assertNotEmpty($role->getRolePermissions());
            self::assertTrue($role->isSystemRole());
            self::assertFalse($role->isDisableReview());
        }
    }
}
