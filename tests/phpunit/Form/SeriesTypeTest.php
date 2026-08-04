<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Entity\Content\Series;
use Inachis\Form\SeriesType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
// use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
// use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

    protected function getExtensions(): array
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $security = $this->createStub(Security::class);

        return [
            new PreloadedExtension([new SeriesType($security, $translator)], []),
        ];
    }

    public function testConfigureOptionsSetsDataClass(): void
    {
        $seriesType = new SeriesType(
            $this->createStub(Security::class),
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
        $seriesType = new SeriesType(
            $this->createStub(Security::class),
            $this->translator(),
        );
        $series = new Series();
        $builder = $this->createMock(FormBuilderInterface::class);

        $expected = [
            ['title', TextType::class, $this->anything()],
            ['subTitle', TextType::class, $this->anything()],
            ['url', TextType::class, $this->anything()],
            ['description', TextareaType::class, $this->anything()],
            ['image', EntityType::class, $this->anything()],
            ['visibility', CheckboxType::class, $this->anything()],
            ['submit', SubmitType::class, $this->anything()],
            ['delete', SubmitType::class, $this->anything()],
        ];

        $this->expectAddCallsInOrder($builder, $expected);
        $seriesType->buildForm($builder, ['data' => $series]);
    }

    public function testBuildFormForExistingSeries(): void
    {
        $seriesType = new SeriesType(
            $this->createStub(Security::class),
            $this->translator(),
        );
        $series = (new Series())->setId(Uuid::uuid1());
        $builder = $this->createMock(FormBuilderInterface::class);

        $expected = [
            ['title', TextType::class, $this->anything()],
            ['subTitle', TextType::class, $this->anything()],
            ['url', TextType::class, $this->anything()],
            ['description', TextareaType::class, $this->anything()],
            ['image', EntityType::class, $this->anything()],
            ['firstDate', DateType::class, $this->anything()],
            ['lastDate', DateType::class, $this->anything()],
            ['bulkCreate', ButtonType::class, $this->anything()],
            ['addItem', ButtonType::class, $this->anything()],
            ['visibility', CheckboxType::class, $this->anything()],
            ['submit', SubmitType::class, $this->anything()],
            ['delete', SubmitType::class, $this->anything()],
            ['remove', SubmitType::class, $this->anything()],
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
