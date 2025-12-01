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
use App\Repository\PageRepository;
use App\Repository\PageRepositoryInterface;
use App\Repository\RevisionRepository;
use App\Repository\UrlRepository;
use App\Util\ContentRevisionCompare;
use ArrayIterator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageControllerTest extends WebTestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private Security|MockObject $security;

    private TranslatorInterface $translator;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetPostAdminRedirectsWhenUrlMissing(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/post/some-post'
        ]);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $pageRepository = $this->createMock(PageRepository::class);
        $urlRepository = $this->getMockBuilder(UrlRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->expects($this->once())
            ->method('findBy')
            ->with(['link' => 'some-post'])
            ->willReturn([]);
        $this->entityManager->method('getRepository')
            ->with(Url::class)
            ->willReturn($urlRepository);

        $controller = $this->getMockBuilder(PageController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['denyAccessUnlessGranted', 'redirectToRoute'])
            ->getMock();
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_post_list', ['type' => 'post'])
            ->willReturn(new RedirectResponse('/redirected'));

        $response = $controller->edit(
            $request,
            $this->createMock(ContentRevisionCompare::class),
            $pageRepository,
            'post',
            'ome-post'
        );
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/redirected', $response->getTargetUrl());
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetPostAdminWithNewPostRendersForm(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/post/new'
        ]);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $pageRepository = $this->createMock(PageRepository::class);
        $urlRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->method('findBy')->willReturn([]);
        $paginator = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $revisionRepository = $this->getMockBuilder(RevisionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $revisionRepository->expects($this->any())
            ->method('getAll')
            ->willReturn($paginator);
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
            ->method('createForm')
            ->willReturn($form);
        $controller->expects($this->once())
            ->method('render')
            ->willReturn(new Response('Rendered form'));
        $response = $controller->edit(
            $request,
            $this->createMock(ContentRevisionCompare::class),
            $pageRepository,
            'post',
            'new'
        );

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Rendered form', $response->getContent());
    }
}
