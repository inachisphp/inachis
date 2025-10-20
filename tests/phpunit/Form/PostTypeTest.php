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
use IntlException;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Emoji\EmojiTransliterator;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PostTypeTest extends TypeTestCase
{
    /**
     * @throws IntlException
     */
    protected function getExtensions(): array
    {
        $router = $this->createMock(RouterInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $transformer = $this->createMock(ArrayCollectionToArrayTransformer::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadata = new ClassMetadata(Category::class);
        $classMetadata->identifier = ['id'];
        $managerRegistry->method('getManagerForClass')->willReturn($entityManager);
        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $doctrineExtension = new DoctrineOrmExtension($managerRegistry);
        return [
            new PreloadedExtension([new PostType($translator, $router, $transformer)], []),
            $doctrineExtension,
        ];
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

        $expectedFields = [ 'title', 'subTitle', 'url', 'content', 'visibility', 'postDate', 'categories',
            'tags', 'language', 'latlong', 'featureSnippet', 'sharingMessage', 'submit' ];
        $this->assertSame($expectedFields, array_keys($view->children));
    }

    public function testBuildFormForExistingPage(): void
    {
        $page = (new Page())->setId(Uuid::uuid1());
        $tag = new Tag();
        $tag->setTitle('Tag One');
        $page->addTag($tag);

        $form = $this->factory->create(PostType::class, $page);
        $view = $form->createView();
        $tagView = $view['tags'];
        $choiceViews = $tagView->vars['choices'];

        $expectedFields = [ 'title', 'subTitle', 'url', 'content', 'visibility', 'postDate', 'categories',
            'tags', 'language', 'latlong', 'featureSnippet', 'sharingMessage', 'submit', 'publish', 'delete' ];
        $this->assertSame($expectedFields, array_keys($view->children));
        $this->assertSame('selected', $choiceViews[0]->attr['selected']);
    }
}
