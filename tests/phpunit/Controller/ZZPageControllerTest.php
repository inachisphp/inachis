<?php

namespace App\Tests\Controller;

use App\Controller\ZZPageController;
use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Revision;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\Url;
use App\Repository\PageRepositoryInterface;
use App\Util\ContentRevisionCompare;
use ArrayIterator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ZZPageControllerTest extends WebTestCase
{
    private ZZPageController $controller;
    private EntityManagerInterface|MockObject $entityManager;
    private Security|MockObject $security;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->controller = new ZZPageController($this->entityManager, $this->security);

        $ref = new \ReflectionClass($this->controller);
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
            ->addMethods(['findOneByLink'])
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepo->expects($this->once())
            ->method('findOneByLink')
            ->with('2025/10/10/sample-post')
            ->willReturn(null);
        $this->entityManager->method('getRepository')
            ->with(Url::class)
            ->willReturn($urlRepo);
        $this->expectException(NotFoundHttpException::class);
        $this->controller->getPost($request, 2025, 10, 10, 'sample-post');
    }

//    public function testGetPostRedirectsIfScheduledOrDraft(): void
//    {
//    }

//    public function testGetPostRedirectsWhenNotDefault(): void
//    {
//    }

    public function testGetPostRendersTemplate(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/2025/10/10/sample-post'
        ]);
        $page = $this->createMock(Page::class);
        $page2 = $this->createMock(Page::class);
        $url = $this->createMock(Url::class);
        $url->method('getContent')->willReturn($page);
        $urlRepository = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['findOneByLink', 'getDefaultUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->expects($this->once())
            ->method('findOneByLink')
            ->with($this->stringContains('2025/10/10/sample-post'))
            ->willReturn($url);
        $urlRepository->method('getDefaultUrl')->willReturn($url);
        $seriesByPostResult = $this->createMock(Series::class);
        $seriesByPostResult->method('getItems')->willReturn(new ArrayCollection([$page2, $page, $page2]));
        $seriesRepository = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['getPublishedSeriesByPost'])
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

        $controller = $this->getMockBuilder(ZZPageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->with('web/post.html.twig')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->getPost($request, '2025', '10', '10', 'sample-post');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testGetPostListAdminRequiresAuthentication(): void
    {
        $request = new Request();

        $controller = $this->getMockBuilder(ZZPageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security])
            ->onlyMethods(['denyAccessUnlessGranted', 'render'])
            ->getMock();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formFactory->method('createBuilder')->willReturn($formBuilder);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            ['form.factory', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $formFactory],
        ]);
        $controller->setContainer($container);

        $iterableMock = $this->getMockBuilder(ArrayIterator::class)
            ->setConstructorArgs([[]])
            ->getMock();
        $paginatorMock = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $paginatorMock->method('getIterator')->willReturn($iterableMock);

        $pageRepositoryMock = $this->createMock(PageRepositoryInterface::class);
        $pageRepositoryMock->method('getFilteredOfTypeByPostDate')->willReturn($paginatorMock);
        $pageRepositoryMock->method('getMaxItemsToShow')->willReturn(10);
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Page::class, $pageRepositoryMock]
            ]);

        $controller->expects($this->once())
            ->method('denyAccessUnlessGranted')
            ->with('IS_AUTHENTICATED_FULLY');
        $controller->expects($this->once())
            ->method('render')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->getPostListAdmin($request);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Rendered OK', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetPostAdminRedirectsWhenUrlMissing(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/post/some-post'
        ]);
        $urlRepository = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['findByLink'])
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->expects($this->once())
            ->method('findByLink')
            ->with('some-post')
            ->willReturn(null);
        $this->entityManager->method('getRepository')
            ->with(Url::class)
            ->willReturn($urlRepository);

        $controller = $this->getMockBuilder(ZZPageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security])
            ->onlyMethods(['denyAccessUnlessGranted', 'redirectToRoute'])
            ->getMock();
        $controller->expects($this->once())
            ->method('denyAccessUnlessGranted')
            ->with('IS_AUTHENTICATED_FULLY');
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_zzpage_getpostadmin', ['type' => 'post'])
            ->willReturn(new RedirectResponse('/redirected'));

        $response = $controller->getPostAdmin(
            $request,
            $this->createMock(ContentRevisionCompare::class),
            'post',
            'ome-post'
        );
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/redirected', $response->getTargetUrl());
    }

    /**
     * @throws Exception
     */
    public function testGetPostAdminWithNewPostRendersForm(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/post/new'
        ]);

        $urlRepository = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['findByLink'])
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->method('findByLink')->willReturn(null);
        $revisionRepository = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['getAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $revisionRepository->expects($this->any())
            ->method('getAll')
            ->willReturn([]);
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Url::class, $urlRepository],
                [Revision::class, $revisionRepository],
            ]);

        $form = $this->createMock(Form::class);
        $form->method('handleRequest');
        $form->method('isSubmitted')->willReturn(false);

        $controller = $this->getMockBuilder(ZZPageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security])
            ->onlyMethods(['denyAccessUnlessGranted', 'createForm', 'render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('denyAccessUnlessGranted')
            ->with('IS_AUTHENTICATED_FULLY');

        $controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);
        $controller->expects($this->once())
            ->method('render')
            ->willReturn(new Response('Rendered form'));
        $response = $controller->getPostAdmin(
            $request,
            $this->createMock(ContentRevisionCompare::class),
            'post',
            'new'
        );

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Rendered form', $response->getContent());
    }

    public function testGetPageDelegatesToGetPost(): void
    {
        $controller = $this->getMockBuilder(ZZPageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security])
            ->onlyMethods(['getPost'])
            ->getMock();
        $controller->expects($this->once())
            ->method('getPost')
            ->with(
                $this->isInstanceOf(Request::class),
                0, 0, 0, ''
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
            ->addMethods(['findOneByTitle'])
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
        $tagRepo = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['findOneByTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $tagRepo->expects($this->once())
            ->method('findOneByTitle')
            ->with('existing-tag')
            ->willReturn($tag);
        $pageRepo = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['getPagesWithTag'])
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
        $controller = $this->getMockBuilder(ZZPageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security])
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
            ->with('web/homepage.html.twig')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->getPostsByTag($request, 'existing-tag');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPostsByCategoryThrowsNotFound(): void
    {
        $request = new Request();
        $categoryRepo = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['findOneByTitle'])
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
            ->addMethods(['findOneByTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryRepository->expects($this->once())
            ->method('findOneByTitle')
            ->with('category-name')
            ->willReturn($category);
        $pageRepository = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['getPagesWithCategory'])
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
        $controller = $this->getMockBuilder(ZZPageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security])
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
            ->with('web/homepage.html.twig')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->getPostsByCategory($request, 'category-name');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    private function renderTestHelper()
    {

    }
}
