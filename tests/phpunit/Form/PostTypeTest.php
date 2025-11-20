<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Form;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Tag;
use App\Form\DataTransformer\ArrayCollectionToArrayTransformer;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ReflectionClass;

class PostTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        /** @var RouterInterface|MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        /** @var TranslatorInterface|MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        /** @var ArrayCollectionToArrayTransformer|MockObject $transformer */
        $transformer = $this->createMock(ArrayCollectionToArrayTransformer::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturnCallback(fn($class) => $this->createMetadata($class));
        $entityManager->method('contains')->willReturn(true);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($entityManager);

        return [
            new PreloadedExtension(
                [
                    new PostType($translator, $router, $transformer),
                    EntityType::class => new EntityType($managerRegistry),
                ],
                []
            ),
            new DoctrineOrmExtension($managerRegistry),
        ];
    }

    /**
     * Create simplified ClassMetadata with string ID for form testing.
     */
    private function createMetadata(string $class): ClassMetadata
    {
        $metadata = new ClassMetadata($class);
        $metadata->identifier = ['id'];
        $metadata->isIdentifierComposite = false;

        $metadata->fieldMappings['id'] = [
            'fieldName' => 'id',
            'type' => 'uuid',
            'id' => true,
            'nullable' => false,
        ];
        $metadata->fieldNames['id'] = 'id';
        $metadata->columnNames['id'] = 'id';
        $metadata->associationMappings = [];

        $ref = new ReflectionClass($class);

        if ($ref->hasProperty('id')) {
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $metadata->reflFields['id'] = $prop; // MUST be a ReflectionProperty, not null
        } else {
            throw new \RuntimeException("$class must have a property 'id'");
        }

        return $metadata;
    }



    public function testConfigureOptionsSetsDataClass(): void
    {
        $form = $this->factory->create(PostType::class, new Page());
        $options = $form->getConfig()->getOptions();

        $this->assertSame(Page::class, $options['data_class']);
        $this->assertSame(['class' => 'form form__post'], $options['attr']);
    }

    public function testBuildFormForNewPage(): void
    {
        $page = new Page();
        $form = $this->factory->create(PostType::class, $page);
        $view = $form->createView();

        $expectedFields = [
            'title', 'subTitle', 'url', 'content', 'visibility', 'postDate',
            'categories', 'tags', 'language', 'latlong', 'featureSnippet',
            'noindex', 'nofollow', 'submit',
        ];

        $this->assertSame($expectedFields, array_keys($view->children));
    }

    public function testBuildFormForExistingPage(): void
    {
        $page = (new Page())->setId(Uuid::uuid1());
        $tag = (new Tag('Tag One'))->setId(Uuid::uuid1());
        $category = (new Category('Category One'))->setId(Uuid::uuid1());

        $page->addTag($tag);
        $page->addCategory($category);


        $form = $this->factory->create(PostType::class, $page);
        $view = $form->createView();

        $expectedFields = [
            'title', 'subTitle', 'url', 'content', 'visibility', 'postDate',
            'categories', 'tags', 'language', 'latlong', 'featureSnippet',
            'noindex', 'nofollow', 'submit', 'modDate', 'publish', 'delete',
        ];

        $this->assertSame($expectedFields, array_keys($view->children));

        $tagView = $view['tags'];
        $choices = $tagView->vars['choices'];
        $this->assertSame('selected', $choices[0]->attr['selected']);
    }
}

