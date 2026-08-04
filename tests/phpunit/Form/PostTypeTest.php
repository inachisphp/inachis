<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Entity\Content\Page;
use Inachis\Form\DataTransformer\ArrayCollectionToArrayTransformer;
use Inachis\Form\PostType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class PostTypeTest extends TestCase
{
    private function translator(): TranslatorInterface
    {
        $m = $this->createStub(TranslatorInterface::class);
        $m->method('trans')->willReturnCallback(fn ($s) => (string) $s);

        return $m;
    }

    private function router(): RouterInterface
    {
        $m = $this->createStub(RouterInterface::class);
        $m->method('generate')->willReturn('/fake/url');

        return $m;
    }

    private function transformer(): ArrayCollectionToArrayTransformer
    {
        return $this->createStub(ArrayCollectionToArrayTransformer::class);
    }

    /**
     * @throws \IntlException
     * @throws Exception
     */
    public function testBuildFormForNewItem(): void
    {
        $postType = new PostType(
            $this->translator(),
            $this->router(),
            $this->createStub(Security::class),
        );

        $page = new Page();
        $builder = $this->createMock(FormBuilderInterface::class);

        $expected = [
            ['title', TextType::class, $this->anything()],
            ['subTitle', TextType::class, $this->anything()],
            ['url', TextType::class, $this->anything()],
            ['content', TextareaType::class, $this->anything()],
            ['visibility', CheckboxType::class, $this->anything()],
            ['showTableOfContents', CheckboxType::class, $this->anything()],
            ['postDate', DateTimeType::class, $this->anything()],
            ['expireDate', DateTimeType::class, $this->anything()],
            ['categories', EntityType::class, $this->anything()],
            ['tags', EntityType::class, $this->anything()],
            ['language', ChoiceType::class, $this->anything()],
            ['latlong', TextType::class, $this->anything()],
            ['featureSnippet', TextareaType::class, $this->anything()],
            ['noindex', CheckboxType::class, $this->anything()],
            ['nofollow', CheckboxType::class, $this->anything()],
            ['submit', SubmitType::class, $this->anything()],
        ];

        $this->expectAddCallsInOrder($builder, $expected);

        $postType->buildForm($builder, ['data' => $page]);
    }

    /**
     * @throws \IntlException
     * @throws \ReflectionException
     * @throws Exception
     */
    public function testBuildFormForExistingItem(): void
    {
        $postType = new PostType(
            $this->translator(),
            $this->router(),
            $this->createStub(Security::class),
            $this->transformer(),
        );

        $page = (new Page())->setId(Uuid::uuid1());
        $builder = $this->createMock(FormBuilderInterface::class);
        $expected = [
            ['title', TextType::class, $this->anything()],
            ['subTitle', TextType::class, $this->anything()],
            ['url', TextType::class, $this->anything()],
            ['content', TextareaType::class, $this->anything()],
            ['visibility', CheckboxType::class, $this->anything()],
            ['showTableOfContents', CheckboxType::class, $this->anything()],
            ['postDate', DateTimeType::class, $this->anything()],
            ['expireDate', DateTimeType::class, $this->anything()],
            ['categories', EntityType::class, $this->anything()],
            ['tags', EntityType::class, $this->anything()],
            ['language', ChoiceType::class, $this->anything()],
            ['latlong', TextType::class, $this->anything()],
            ['featureSnippet', TextareaType::class, $this->anything()],
            ['noindex', CheckboxType::class, $this->anything()],
            ['nofollow', CheckboxType::class, $this->anything()],
            ['submit', SubmitType::class, $this->anything()],
            // Extra fields added only for existing item:
            ['updatedAt', DateTimeType::class, $this->anything()],
            ['publish', SubmitType::class, $this->anything()],
            ['delete', SubmitType::class, $this->anything()],
        ];

        $this->expectAddCallsInOrder($builder, $expected);
        $postType->buildForm($builder, ['data' => $page]);
    }

    public function testConfigureOptions(): void
    {
        $postType = new PostType(
            $this->translator(),
            $this->router(),
            $this->createStub(Security::class),
        );

        $resolver = new OptionsResolver();
        $postType->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertSame('form form__post', $options['attr']['class']);
        $this->assertSame(Page::class, $options['data_class']);
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
