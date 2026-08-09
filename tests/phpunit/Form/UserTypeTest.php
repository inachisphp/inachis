<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Entity\Security\Role;
use Inachis\Entity\Security\RolePermission;
use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\UserType;
use Inachis\Security\Authorisation\PermissionResolver;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserTypeTest extends TestCase
{
    private TranslatorInterface $translator;

    private Security $security;

    private PermissionResolver $permissionResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createStub(TranslatorInterface::class);

        $this->security = $this->createMock(Security::class);

        $this->permissionResolver = new PermissionResolver();
    }

    public function testAssignedRolesAreNotAddedWithoutEditPermission(): void
    {
        $currentUser = new User('current');
        $currentUser->setId(Uuid::uuid4());

        $this->security
            ->method('getUser')
            ->willReturn($currentUser);

        $builder = $this->createMock(FormBuilderInterface::class);

        $addedFields = [];

        $builder
            ->expects($this->atLeastOnce())
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    string $type,
                    array $options = [],
                ) use (
                    $builder,
                    &$addedFields,
                ): FormBuilderInterface {
                    $addedFields[$name] = [
                        'type' => $type,
                        'options' => $options,
                    ];

                    return $builder;
                },
            );

        $formType = new UserType(
            $this->permissionResolver,
            $this->translator,
            $this->security,
        );

        $formType->buildForm($builder, []);

        $this->assertArrayNotHasKey('assignedRoles', $addedFields);
    }

    public function testNewUserHasEditableUsername(): void
    {
        $currentUser = new User('current');
        $currentUser->setId(Uuid::uuid4());

        $this->security
            ->method('getUser')
            ->willReturn($currentUser);

        $builder = $this->createMock(FormBuilderInterface::class);

        $fields = [];

        $builder
            ->expects($this->atLeastOnce())
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    string $type,
                    array $options = [],
                ) use (
                    $builder,
                    &$fields,
                ): FormBuilderInterface {
                    $fields[$name] = [
                        'type' => $type,
                        'options' => $options,
                    ];

                    return $builder;
                },
            );

        $formType = new UserType(
            $this->permissionResolver,
            $this->translator,
            $this->security,
        );

        $formType->buildForm($builder, [
            'data' => new User('new-user'),
        ]);

        $this->assertArrayHasKey('username', $fields);
        $this->assertSame(
            TextType::class,
            $fields['username']['type'],
        );
        $this->assertTrue(
            $fields['username']['options']['attr']['autofocus'],
        );
        $this->assertFalse(
            $fields['username']['options']['disabled'],
        );
    }

    public function testExistingUserHasHiddenUsername(): void
    {
        $currentUser = new User('current');
        $currentUser->setId(Uuid::uuid4());

        $targetUser = new User('target');
        $targetUser->setId(Uuid::uuid4());

        $this->security
            ->method('getUser')
            ->willReturn($currentUser);

        $builder = $this->createMock(FormBuilderInterface::class);

        $fields = [];

        $builder
            ->expects($this->atLeastOnce())
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    string $type,
                    array $options = [],
                ) use (
                    $builder,
                    &$fields,
                ): FormBuilderInterface {
                    $fields[$name] = [
                        'type' => $type,
                        'options' => $options,
                    ];

                    return $builder;
                },
            );

        $formType = new UserType(
            $this->permissionResolver,
            $this->translator,
            $this->security,
        );

        $formType->buildForm($builder, [
            'data' => $targetUser,
        ]);

        $this->assertArrayHasKey('username', $fields);
        $this->assertSame(
            HiddenType::class,
            $fields['username']['type'],
        );
        $this->assertTrue(
            $fields['username']['options']['disabled'],
        );
    }

    public function testCurrentUserCanEditTheirOwnDetails(): void
    {
        $currentUser = new User('current');
        $currentUser->setId(Uuid::uuid4());

        $this->security
            ->method('getUser')
            ->willReturn($currentUser);

        $builder = $this->createMock(FormBuilderInterface::class);

        $fields = [];

        $builder
            ->expects($this->atLeastOnce())
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    string $type,
                    array $options = [],
                ) use (
                    $builder,
                    &$fields,
                ): FormBuilderInterface {
                    $fields[$name] = [
                        'type' => $type,
                        'options' => $options,
                    ];

                    return $builder;
                },
            );

        $formType = new UserType(
            $this->permissionResolver,
            $this->translator,
            $this->security,
        );

        $formType->buildForm($builder, [
            'data' => $currentUser,
        ]);

        $this->assertArrayHasKey('displayName', $fields);
        $this->assertFalse(
            $fields['displayName']['options']['disabled'],
        );

        $this->assertArrayHasKey('email', $fields);
        $this->assertFalse(
            $fields['email']['options']['disabled'],
        );

        $this->assertArrayHasKey('timezone', $fields);
        $this->assertFalse(
            $fields['timezone']['options']['disabled'],
        );

        $this->assertArrayHasKey('color', $fields);
        $this->assertSame(
            ChoiceType::class,
            $fields['color']['type'],
        );

        $this->assertArrayHasKey('submit', $fields);
        $this->assertSame(
            SubmitType::class,
            $fields['submit']['type'],
        );
    }

    public function testCurrentUserWithTotpEnabledCanDisableOrRegenerateTotp(): void
    {
        $currentUser = $this->getMockBuilder(User::class)
            ->setConstructorArgs(['current'])
            ->onlyMethods(['isTotpEnabled'])
            ->getMock();

        $currentUser->setId(Uuid::uuid4());

        $currentUser
            ->expects($this->atLeastOnce())
            ->method('isTotpEnabled')
            ->willReturn(true);

        $this->security
            ->method('getUser')
            ->willReturn($currentUser);

        $builder = $this->createMock(FormBuilderInterface::class);

        $fields = [];

        $builder
            ->expects($this->atLeastOnce())
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    string $type,
                    array $options = [],
                ) use (
                    $builder,
                    &$fields,
                ): FormBuilderInterface {
                    $fields[$name] = [
                        'type' => $type,
                        'options' => $options,
                    ];

                    return $builder;
                },
            );

        $formType = new UserType(
            $this->permissionResolver,
            $this->translator,
            $this->security,
        );

        $formType->buildForm($builder, [
            'data' => $currentUser,
        ]);

        $this->assertArrayHasKey('disableTotp', $fields);
        $this->assertSame(
            SubmitType::class,
            $fields['disableTotp']['type'],
        );

        $this->assertArrayHasKey('regenerateCodes', $fields);
        $this->assertSame(
            SubmitType::class,
            $fields['regenerateCodes']['type'],
        );

        $this->assertArrayNotHasKey('enableTotp', $fields);
    }

    public function testNonCurrentUserCanBeDeletedWithPermission(): void
    {
        $currentUser = new User('current');
        $currentUser->setId(Uuid::uuid4());

        $targetUser = new User('target');
        $targetUser->setId(Uuid::uuid4());

        $this->security
            ->method('getUser')
            ->willReturn($currentUser);

        $this->grantPermission(
            $currentUser,
            PermissionResource::USER,
            PermissionAction::DELETE,
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $fields = [];

        $builder
            ->expects($this->atLeastOnce())
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    string $type,
                    array $options = [],
                ) use (
                    $builder,
                    &$fields,
                ): FormBuilderInterface {
                    $fields[$name] = [
                        'type' => $type,
                        'options' => $options,
                    ];

                    return $builder;
                },
            );

        $formType = new UserType(
            $this->permissionResolver,
            $this->translator,
            $this->security,
        );

        $formType->buildForm($builder, [
            'data' => $targetUser,
        ]);

        $this->assertArrayHasKey('delete', $fields);
        $this->assertSame(
            SubmitType::class,
            $fields['delete']['type'],
        );
    }

    public function testBuildFormThrowsWhenCurrentUserIsNotAuthenticated(): void
    {
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $builder = $this->createMock(FormBuilderInterface::class);

        $formType = new UserType(
            $this->permissionResolver,
            $this->translator,
            $this->security,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Current user must be authenticated to build UserType form.',
        );

        $formType->buildForm($builder, []);
    }

    public function testConfigureOptionsUsesUserDataClass(): void
    {
        $resolver = new OptionsResolver();

        $formType = new UserType(
            $this->permissionResolver,
            $this->translator,
            $this->security,
        );

        $formType->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertSame(
            User::class,
            $options['data_class'],
        );
    }

    /**
     * Assigns a permission to a user through the real role/permission model.
     */
    private function grantPermission(
        User $user,
        PermissionResource $resource,
        PermissionAction $action,
    ): void {
        $role = new Role();
        $role->setName('Test Role');

        $permission = new RolePermission();
        $permission
            ->setResource($resource)
            ->setAction($action);

        $role->addRolePermission($permission);

        $user->addAssignedRole($role);
    }
}
