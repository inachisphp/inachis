<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Inachis\Entity\Content\Series;
use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\SeriesType;
use Inachis\Provider\TimezoneProvider;
use Inachis\Security\Authorisation\PermissionResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
class SeriesTypeTest extends TypeTestCase
{
    private function translator(): TranslatorInterface
    {
        $m = $this->createStub(TranslatorInterface::class);
        $m->method('trans')->willReturnCallback(fn ($s) => (string) $s);

        return $m;
    }

    private function createGrantedUser(): User
    {
        $permissions = [];
        foreach (PermissionResource::cases() as $resource) {
            foreach (PermissionAction::cases() as $action) {
                $permissions[] = new class($resource, $action) {
                    public function __construct(
                        private readonly PermissionResource $r,
                        private readonly PermissionAction $a,
                    ) {
                    }

                    public function getResource(): PermissionResource
                    {
                        return $this->r;
                    }

                    public function getAction(): PermissionAction
                    {
                        return $this->a;
                    }
                };
            }
        }

        $role = new class($permissions) {
            public function __construct(private readonly array $permissions)
            {
            }

            public function getRolePermissions(): array
            {
                return $this->permissions;
            }
        };

        $user = $this->createStub(User::class);
        $user->method('getAssignedRoles')->willReturn(new ArrayCollection([$role]));

        return $user;
    }

    protected function getExtensions(): array
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($this->createGrantedUser());
        $permissionResolver = new PermissionResolver();

        // Instantiate directly instead of stubbing:
        $timezoneProvider = new TimezoneProvider('UTC');

        return [
            new PreloadedExtension([new SeriesType($permissionResolver, $security, $timezoneProvider, $translator)], []),
        ];
    }

    public function testConfigureOptionsSetsDataClass(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($this->createGrantedUser());
        $permissionResolver = new PermissionResolver();
        $timezoneProvider = new TimezoneProvider('UTC');

        $seriesType = new SeriesType(
            $permissionResolver,
            $security,
            $timezoneProvider,
            $this->translator(),
        );
        $resolver = new OptionsResolver();
        $seriesType->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertSame('form form__post form__series', $options['attr']['class']);
        $this->assertSame(Series::class, $options['data_class']);
    }

    public function testBuildFormForNewSeries(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($this->createGrantedUser());
        $permissionResolver = new PermissionResolver();
        $timezoneProvider = new TimezoneProvider('UTC');

        $seriesType = new SeriesType(
            $permissionResolver,
            $security,
            $timezoneProvider,
            $this->translator(),
        );
        $series = new Series();
        $builder = $this->createMock(FormBuilderInterface::class);

        $expected = [
            ['title', TextType::class],
            ['subTitle', TextType::class],
            ['url', TextType::class],
            ['description', TextareaType::class],
            ['image', HiddenType::class],
            ['submit', SubmitType::class],
        ];

        $this->expectAddCallsInOrder($builder, $expected);
        $seriesType->buildForm($builder, ['data' => $series]);
    }

    public function testBuildFormForExistingSeries(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($this->createGrantedUser());
        $permissionResolver = new PermissionResolver();
        $timezoneProvider = new TimezoneProvider('UTC');

        $seriesType = new SeriesType(
            $permissionResolver,
            $security,
            $timezoneProvider,
            $this->translator(),
        );
        $series = (new Series())->setId(Uuid::uuid1());
        $builder = $this->createMock(FormBuilderInterface::class);

        $expected = [
            ['title', TextType::class],
            ['subTitle', TextType::class],
            ['url', TextType::class],
            ['description', TextareaType::class],
            ['image', HiddenType::class],
            ['firstDate', DateType::class],
            ['lastDate', DateType::class],
            ['bulkCreate', ButtonType::class],
            ['addItem', ButtonType::class],
            ['visible', CheckboxType::class],
            ['submit', SubmitType::class],
            ['delete', SubmitType::class],
            ['remove', SubmitType::class],
        ];

        $this->expectAddCallsInOrder($builder, $expected);
        $seriesType->buildForm($builder, ['data' => $series]);
    }

    /**
     * Helper to assert add() calls in exact order.
     */
    private function expectAddCallsInOrder(FormBuilderInterface $builder, array $expectedCalls): void
    {
        $callIndex = 0;

        $builder->expects($this->exactly(count($expectedCalls)))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use (&$callIndex, $expectedCalls, $builder) {
                [$expectedName, $expectedType] = $expectedCalls[$callIndex];
                $this->assertSame($expectedName, $name);
                $this->assertSame($expectedType, $type);

                if (isset($options['choice_attr']) && is_callable($options['choice_attr'])) {
                    $result = $options['choice_attr']('fakeChoice', 'fakeKey', 'fakeValue');
                    $this->assertSame(['selected' => 'selected'], $result);
                }

                ++$callIndex;

                return $builder;
            });
    }
}
