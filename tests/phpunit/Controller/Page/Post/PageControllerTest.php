<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Post;

use App\Controller\Page\Post\PageController;
use App\Entity\Page;
use App\Entity\Revision;
use App\Entity\Url;
use App\Repository\PageRepositoryInterface;
use App\Util\ContentRevisionCompare;
use ArrayIterator;
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
use Symfony\Contracts\Translation\TranslatorInterface;

class PageControllerTest extends WebTestCase
{
    private PageController $controller;
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
        $this->controller = new PageController($this->entityManager, $this->security, $this->translator);

        $ref = new ReflectionClass($this->controller);
        foreach (['entityManager', 'security'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setValue($this->controller, $this->$prop);
        }
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn(null);
        $this->controller->setContainer($container);
    }

    /**
     * @throws Exception
     */
    public function testGetPostListAdminRequiresAuthentication(): void
    {
        $request = new Request();

        $controller = $this->getMockBuilder(PageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
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
        $response = $controller->list($request);
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

        $controller = $this->getMockBuilder(PageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['denyAccessUnlessGranted', 'redirectToRoute'])
            ->getMock();
        $controller->expects($this->once())
            ->method('denyAccessUnlessGranted')
            ->with('IS_AUTHENTICATED_FULLY');
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_post_list', ['type' => 'post'])
            ->willReturn(new RedirectResponse('/redirected'));

        $response = $controller->edit(
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

        $controller = $this->getMockBuilder(PageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
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
        $response = $controller->edit(
            $request,
            $this->createMock(ContentRevisionCompare::class),
            'post',
            'new'
        );

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Rendered form', $response->getContent());
    }

    private function renderTestHelper(): void
    {
    }
}
