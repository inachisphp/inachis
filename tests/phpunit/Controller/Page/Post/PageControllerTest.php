<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Post;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Inachis\Controller\Page\Post\PageController;
use Inachis\Entity\Content\{Revision, Url};
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\ReviewThreadRepository;
use Inachis\Repository\Content\RevisionRepository;
use Inachis\Repository\Content\TagRepository;
use Inachis\Repository\Content\UrlRepository;
use Inachis\Service\Page\PageBulkActionService;
use Inachis\Service\Page\ReviewRebaseService;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use Inachis\Util\ContentRevisionCompare;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class PageControllerTest extends InachisControllerTestCase
{
    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetPostAdminRedirectsWhenUrlMissing(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/post/some-post'
        ]);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $pageRepository = $this->createStub(PageRepository::class);
        $urlRepository = $this->getMockBuilder(UrlRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->expects($this->once())
            ->method('findBy')
            ->with(['link' => 'some-post'])
            ->willReturn([]);
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Url::class)
            ->willReturn($urlRepository);

        $controller = $this->getMockBuilder(PageController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['denyAccessUnlessGranted', 'redirectToRoute'])
            ->getMock();
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_post_list', ['type' => 'post'])
            ->willReturn(new RedirectResponse('/redirected'));
        $revisionRepository = $this->createStub(RevisionRepository::class);

        $response = $controller->edit(
            $request,
            $this->createStub(ContentRevisionCompare::class),
            $this->createStub(PageBulkActionService::class),
            $pageRepository,
            $revisionRepository,
            $this->createStub(ReviewThreadRepository::class),
            $this->createStub(ReviewRebaseService::class),
            $this->createStub(TagRepository::class),
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

        $pageRepository = $this->createStub(PageRepository::class);
        $urlRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRepository->expects($this->once())->method('findBy')->willReturn([]);
        $paginator = $this->getStubBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->getStub();
        $revisionRepository = $this->getMockBuilder(RevisionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $revisionRepository->method('getAll')
            ->willReturn($paginator);
        $this->entityManager->expects($this->atLeastOnce())
            ->method('getRepository')
            ->willReturnMap([
                [Url::class, $urlRepository],
                [Revision::class, $revisionRepository],
                [Tag::class, $this->createStub(TagRepository::class)],
            ]);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('handleRequest');
        $form->expects($this->once())->method('isSubmitted')->willReturn(false);

        $controller = $this->getMockBuilder(PageController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['denyAccessUnlessGranted', 'createForm', 'render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);
        $controller->expects($this->once())
            ->method('render')
            ->willReturn(new Response('Rendered form'));
        $revisionRepository = $this->createStub(RevisionRepository::class);

        $response = $controller->edit(
            $request,
            $this->createStub(ContentRevisionCompare::class),
            $this->createStub(PageBulkActionService::class),
            $pageRepository,
            $revisionRepository,
            $this->createStub(ReviewThreadRepository::class),
            $this->createStub(ReviewRebaseService::class),
            $this->createStub(TagRepository::class),
            'post',
            'new'
        );
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Rendered form', $response->getContent());
    }
}
