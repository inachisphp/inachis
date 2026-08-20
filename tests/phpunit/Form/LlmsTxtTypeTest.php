<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\LlmsTxtType;
use Inachis\Security\Authorisation\PermissionResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LlmsTxtTypeTest extends TestCase
{
    private PermissionResolver $permissionResolver;
    private Security&MockObject $security;
    private TranslatorInterface&MockObject $translator;
    private LlmsTxtType $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionResolver = new PermissionResolver();
        $this->security = $this->createMock(Security::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formType = new LlmsTxtType(
            $this->permissionResolver,
            $this->security,
            $this->translator,
        );
    }

    #[Test]
    public function itBuildsFormWhenUserHasEditPermission(): void
    {
        $permission = new class {
            public function getResource(): PermissionResource
            {
                return PermissionResource::CRAWLER;
            }

            public function getAction(): PermissionAction
            {
                return PermissionAction::EDIT;
            }
        };

        $role = new class($permission) {
            public function __construct(private readonly object $permission)
            {
            }

            /**
             * @return list<object>
             */
            public function getRolePermissions(): array
            {
                return [$this->permission];
            }
        };

        $user = $this->createMock(User::class);
        $user->method('getAssignedRoles')->willReturn(new ArrayCollection([$role]));

        $this->security->method('getUser')->willReturn($user);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('admin.button.save', [], 'messages')
            ->willReturn('Save');

        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->expects(self::exactly(2))
            ->method('add')
            ->willReturnCallback(function (string $name, string $type, array $options) use ($builder) {
                if ('llms_txt' === $name) {
                    self::assertSame(TextareaType::class, $type);
                    self::assertSame('', $options['disabled']);
                } elseif ('submit' === $name) {
                    self::assertSame(SubmitType::class, $type);
                    self::assertSame('<span class="material-icons">save</span> Save', $options['label']);
                    self::assertTrue($options['label_html']);
                }

                return $builder;
            });

        $this->formType->buildForm($builder, []);
    }

    #[Test]
    public function itBuildsFormWhenUserDoesNotHaveEditPermission(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getAssignedRoles')->willReturn(new ArrayCollection([]));

        $this->security->method('getUser')->willReturn($user);

        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->expects(self::once())
            ->method('add')
            ->with('llms_txt', TextareaType::class, self::callback(
                static fn (array $options): bool => 'disabled' === $options['disabled']
            ))
            ->willReturnSelf();

        $this->formType->buildForm($builder, []);
    }

    #[Test]
    public function itConfiguresOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver
            ->expects(self::once())
            ->method('setDefaults')
            ->with([
                'attr' => [
                    'class' => 'form form__post form__tab',
                ],
            ])
            ->willReturnSelf();

        $this->formType->configureOptions($resolver);
    }
}
