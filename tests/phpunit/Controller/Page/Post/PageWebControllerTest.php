<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Post;

use App\Controller\Page\Post\PageWebController;
use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\Url;
use App\Repository\PageRepository;
use App\Repository\SeriesRepository;
use App\Repository\TagRepository;
use App\Repository\UrlRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageWebControllerTest extends WebTestCase
{
    private PageWebController $controller;
    private EntityManagerInterface|MockObject $entityManager;
    private Security|MockObject $security;

    private TranslatorInterface $translator;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->controller = new PageWebController($this->entityManager, $this->security, $this->translator);

        $ref = new ReflectionClass($this->controller);
        foreach (['entityManager', 'security'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setValue($this->controller, $this->$prop);
        }
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn(null);
        $this->controller->setContainer($container);
    }

    public function testGetPostThrowsNotFoundWhenUrlMissing(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/2025/10/10/sample-post'
        ]);
        $urlRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['link' => '2025/10/10/sample-post'])
            ->willReturn(null);
        $this->entityManager->method('getRepository')
            ->with(Url::class)
            ->willReturn($urlRepo);
        $this->expectException(NotFoundHttpException::class);
        $this->controller->getPost($request, 2025, 10, 10, 'sample-post');
    }

    public function testGetPostRedirectsIfScheduledOrDraft(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/2025/10/10/sample-post'
        ]);
        $page = $this->createMock(Page::class);
        $page->method('isScheduledPage')->willReturn(true);
        $url = $this->createMock(Url::class);
        $url->method('getContent')->willReturn($page);
        $urlRepository = $this->getMockBuilder(UrlRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['link' => '2025/10/10/sample-post'])
            ->willReturn($url);
        $urlRepository->method('getDefaultUrl')->willReturn($url);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Url::class, $urlRepository],
            ]);

        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['render'])
            ->getMock();
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/');
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($router) {
            if (str_contains($id, 'router')) {
                return $router;
            }
            return null;
        });
        $controller->setContainer($container);
        $response = $controller->getPost($request, '2025', '10', '10', 'sample-post');
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/', $response->headers->get('Location'));
    }

    public function testGetPostRedirectsWhenNotDefault(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/2025/10/10/sample-post'
        ]);
        $url = $this->createMock(Url::class);
        $url->method('isDefault')->willReturn(false);
        $url2 = $this->createMock(Url::class);
        $url2->method('isDefault')->willReturn(true);
        $urlRepository = $this->getMockBuilder(UrlRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->expects($this->once())
            ->method('findOneBy')
            ->with([ 'link' => '2025/10/10/sample-post' ])
            ->willReturn($url);
        $urlRepository->method('getDefaultUrl')->willReturn($url2);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Url::class, $urlRepository],
            ]);

        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['render'])
            ->getMock();
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/');
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($router) {
            if (str_contains($id, 'router')) {
                return $router;
            }
            return null;
        });
        $controller->setContainer($container);
        $response = $controller->getPost($request, '2025', '10', '10', 'sample-post');
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/', $response->headers->get('Location'));
    }

    public function testGetPostRendersTemplate(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/2025/10/10/sample-post'
        ]);
        $page = $this->createMock(Page::class);
        $page2 = $this->createMock(Page::class);
        $url = $this->createMock(Url::class);
        $url->method('getContent')->willReturn($page);
        $urlRepository = $this->getMockBuilder(UrlRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->expects($this->once())
            ->method('findOneBy')
            ->with([ 'link' => '2025/10/10/sample-post' ])
            ->willReturn($url);
        $urlRepository->method('getDefaultUrl')->willReturn($url);
        $seriesByPostResult = $this->createMock(Series::class);
        $seriesByPostResult->method('getItems')->willReturn(new ArrayCollection([$page2, $page, $page2]));
        $seriesRepository = $this->getMockBuilder(SeriesRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $seriesRepository->expects($this->once())
            ->method('getPublishedSeriesByPost')
            ->willReturn($seriesByPostResult);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Url::class, $urlRepository],
                [Series::class, $seriesRepository],
            ]);

        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->with('web/pages/post.html.twig')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->getPost($request, '2025', '10', '10', 'sample-post');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPageDelegatesToGetPost(): void
    {
        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['getPost'])
            ->getMock();
        $controller->expects($this->once())
            ->method('getPost')
            ->with(
                $this->isInstanceOf(Request::class),
                0,
                0,
                0,
                ''
            )
            ->willReturn(new Response('OK'));
        $request = new Request();
        $response = $controller->getPage($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('OK', $response->getContent());
    }

    public function testGetPostsByTagThrowsNotFound(): void
    {
        $request = new Request();
        $tagRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Tag::class, $tagRepository]
            ]);
        $this->expectException(NotFoundHttpException::class);
        $this->controller->getPostsByTag($request, 'nonexistent-tag');
    }

    public function testGetPostsByTagRendersTemplate(): void
    {
        $request = new Request();
        $tag = $this->createMock(Tag::class);
        $pages = [$this->createMock(Page::class)];
        $tagRepo = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tagRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'existing-tag'])
            ->willReturn($tag);
        $pageRepo = $this->getMockBuilder(PageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageRepo->expects($this->once())
            ->method('getPagesWithTag')
            ->with($tag)
            ->willReturn($pages);
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Tag::class, $tagRepo],
                [Page::class, $pageRepo]
            ]);
        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['render'])
            ->getMock();
        $ref = new ReflectionClass($controller);
        foreach (['entityManager', 'security'] as $prop) {
            if ($ref->hasProperty($prop)) {
                $property = $ref->getProperty($prop);
                $property->setValue($controller, $this->$prop);
            }
        }
        $controller->expects($this->once())
            ->method('render')
            ->with('web/pages/homepage.html.twig')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->getPostsByTag($request, 'existing-tag');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPostsByCategoryThrowsNotFound(): void
    {
        $request = new Request();
        $categoryRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Category::class, $categoryRepo]
            ]);
        $this->expectException(NotFoundHttpException::class);
        $this->controller->getPostsByCategory($request, 'missing-category');
    }

    public function testGetPostsByCategoryRendersTemplate(): void
    {
        $request = new Request();
        $category = $this->createMock(Category::class);
        $pages = [$this->createMock(Page::class)];
        $categoryRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'category-name'])
            ->willReturn($category);
        $pageRepository = $this->getMockBuilder(PageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageRepository->expects($this->once())
            ->method('getPagesWithCategory')
            ->with($category)
            ->willReturn($pages);
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Category::class, $categoryRepository],
                [Page::class, $pageRepository]
            ]);
        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['render'])
            ->getMock();
        $ref = new ReflectionClass($controller);
        foreach (['entityManager', 'security'] as $prop) {
            if ($ref->hasProperty($prop)) {
                $property = $ref->getProperty($prop);
                $property->setValue($controller, $this->$prop);
            }
        }
        $controller->expects($this->once())
            ->method('render')
            ->with('web/pages/homepage.html.twig')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->getPostsByCategory($request, 'category-name');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
