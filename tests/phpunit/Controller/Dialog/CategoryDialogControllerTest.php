<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Dialog;

use App\Controller\Dialog\CategoryDialogController;
use App\Controller\Dialog\ImageGalleryDialogController;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use ArrayIterator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Nonstandard\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class CategoryDialogControllerTest extends WebTestCase
{
    protected CategoryRepository $categoryRepository;
    protected CategoryDialogController $controller;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $this->controller = $this->getMockBuilder(CategoryDialogController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['render'])
            ->getMock();
        $this->controller->expects($this->atLeast(0))->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        parent::setUp();
    }
    public function testGetCategoryManagerContent(): void
    {
        $this->categoryRepository->expects($this->never())->method('findAll');
        $result = $this->controller->getCategoryManagerContent($this->categoryRepository);
        $this->assertEquals('rendered:inadmin/dialog/category-manager.html.twig', $result->getContent());
    }
    public function testGetCategoryManagerList(): void
    {
        $this->categoryRepository->expects($this->never())->method('findAll');
        $result = $this->controller->getCategoryManagerList($this->categoryRepository);
        $this->assertEquals('rendered:inadmin/dialog/category-manager-list.html.twig', $result->getContent());
    }

    public function testGetCategoryManagerListContentRootCategory(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/ax/categoryList/get'
        ]);
        $category = (new Category('test-category'))->setId(Uuid::uuid1());
        $this->categoryRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([$category]);
        $result = $this->controller->getCategoryManagerListContent($request, $this->categoryRepository);
        $this->assertJson($result->getContent());
        $result = json_decode($result->getContent());
        $this->assertEquals('test-category', $result->items[0]->text);
        $this->assertEquals(1, $result->totalCount);
    }

    public function testGetCategoryManagerListContentChildCategory(): void
    {
        $request = new Request([], [
            'q' => 'test-category',
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/categoryList/get'
        ]);
        $category = (new Category('test-category'))->setId(Uuid::uuid1());
        $paginator = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$category, $category]));

        $this->categoryRepository->expects($this->once())
            ->method('findByTitleLike')
            ->willReturn($paginator);
        $result = $this->controller->getCategoryManagerListContent($request, $this->categoryRepository);
        $this->assertJson($result->getContent());
        $result = json_decode($result->getContent());
        $this->assertEquals('test-category', $result->items[0]->text);
        $this->assertEquals(1, $result->totalCount);
    }

    public function testSaveCategoryManagerContentExistingCategory(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'id' => $uuid->toString(),
        ], [], [], [], [
            'REQUEST_URI' => 'incc/ax/categoryManager/save'
        ]);
        $category = (new Category('test-category'))->setId($uuid);
        $this->categoryRepository->expects($this->atLeastOnce())
            ->method('findOneBy')
            ->willReturn($category);
        $result = $this->controller->saveCategoryManagerContent($request, $this->categoryRepository);
        $this->assertStringContainsString('success', $result->getContent());
    }

    public function testSaveCategoryManagerContentNewCategory(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'id' => '-1',
            'parentID' => $uuid->toString(),
        ], [], [], [], [
            'REQUEST_URI' => 'incc/ax/categoryManager/save'
        ]);
        $this->categoryRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(new Category());
        $result = $this->controller->saveCategoryManagerContent($request, $this->categoryRepository);
        $this->assertStringContainsString('success', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetCategoryUsages(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'id' => $uuid->toString(),
        ], [], [], [], [
            'REQUEST_URI' => 'incc/ax/categoryManager/usage'
        ]);
        $category = (new Category('test-category'))->setId($uuid);
        $category->addChild(new Category('test-sub-category'));
        $this->categoryRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($category);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->atLeastOnce())
            ->method('getPagesWithCategoryCount')
            ->willReturn(1);
        $result = $this->controller->getCategoryUsages($request, $this->categoryRepository, $pageRepository);
        $this->assertEquals('{"count":2}', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testDeleteCategoryError(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'id' => $uuid->toString(),
        ], [], [], [], [
            'REQUEST_URI' => 'incc/ax/categoryManager/delete'
        ]);
        $category = (new Category('test-category'))->setId($uuid);
        $category->addChild(new Category('test-sub-category'));
        $this->categoryRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($category);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())
            ->method('getPagesWithCategoryCount')
            ->willReturn(1);
        $result = $this->controller->deleteCategory($request, $this->categoryRepository, $pageRepository);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result->getStatusCode());
        $this->assertStringContainsString('error', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testDeleteCategory(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'id' => $uuid->toString(),
        ], [], [], [], [
            'REQUEST_URI' => 'incc/ax/categoryManager/delete'
        ]);
        $category = (new Category('test-category'))->setId($uuid);
        $category->addChild(new Category('test-sub-category'));
        $this->categoryRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($category);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())
            ->method('getPagesWithCategoryCount')
            ->willReturn(0);
        $result = $this->controller->deleteCategory($request, $this->categoryRepository, $pageRepository);
        $this->assertEquals('{}', $result->getContent());
    }
}
