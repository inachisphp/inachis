<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Entity\Security\Role;
use Inachis\Form\UserType;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Inachis\Security\Authorisation\PermissionResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserTypeTest extends TestCase
{
    public function testAssignedRolesUsesUuidStringChoiceValues(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);

        $capturedOptions = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->atLeastOnce())
            ->method('add')
            ->willReturnCallback(function (string $name, string $type, array $options = []) use ($builder, &$capturedOptions): FormBuilderInterface {
                if ('assignedRoles' === $name) {
                    $capturedOptions = $options;
                }

                return $builder;
            });

        $permissionResolver = new PermissionResolver();

        $currentUser = new \Inachis\Entity\User\User('current');
        $security->method('getUser')->willReturn($currentUser);

        $formType = new UserType($permissionResolver, $translator, $security);
        $formType->buildForm($builder, []);

        $this->assertArrayHasKey('choice_value', $capturedOptions);
        $this->assertFalse($capturedOptions['by_reference']);

        $role = new Role();
        $role->setName('Administrator');
        $role->setSlug('administrator');

        $uuid = Uuid::uuid4();
        $reflectionProperty = new \ReflectionProperty(Role::class, 'id');
        $reflectionProperty->setValue($role, $uuid);

        $this->assertSame($uuid->toString(), $capturedOptions['choice_value']($role));
    }
}
