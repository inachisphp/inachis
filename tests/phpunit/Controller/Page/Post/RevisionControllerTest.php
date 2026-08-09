<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Post;

use Inachis\Controller\Page\Post\RevisionController;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Revision;
use Inachis\Entity\Content\Url;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\RevisionRepository;
use Inachis\Service\Content\Page\RevisionDiffRenderer;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RevisionControllerTest extends InachisControllerTestCase
{
    protected RevisionController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = $this->getMockBuilder(RevisionController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['addFlash', 'getCurrentUser', 'redirect', 'render'])
            ->getMock();
    }

    public function testDiffEmptyRevision(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/diff/{id}',
        ]);

        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revisionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->controller->expects($this->never())
            ->method('render');

        $this->expectException(NotFoundHttpException::class);

        $this->controller->diff(
            $request,
            new RevisionDiffRenderer(),
            $revisionRepository,
        );
    }

    public function testDiffPageNotFound(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/diff/{id}',
        ]);

        $revisionRepository = $this->createMock(RevisionRepository::class);

        $revision = new Revision();
        $page = new Page();
        $page->setId(Uuid::uuid1());
        $revision->setPage($page);

        $revisionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($revision);

        $this->controller->expects($this->never())
            ->method('render');

        $this->expectException(RuntimeException::class);

        $this->controller->diff(
            $request,
            new RevisionDiffRenderer(),
            $revisionRepository,
        );
    }

    /**
     * @throws Exception
     */
    public function testDiff(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/diff/{id}',
        ]);

        $page = (new Page('test-page'))
            ->setId(Uuid::uuid1())
            ->setTitle('')
            ->setContent('test edited');

        $url = new Url($page, 'test-link');
        $page->addUrl($url);

        $revision = (new Revision())
            ->setPage($page)
            ->setTitle('');

        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revisionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($revision);

        $this->controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(
                static function (string $template, array $data): Response {
                    return new Response('rendered:'.$template);
                },
            );

        $result = $this->controller->diff(
            $request,
            new RevisionDiffRenderer(),
            $revisionRepository,
        );

        $this->assertSame(
            'rendered:inadmin/page/post/track_changes.html.twig',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testDoRevert(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/diff/{id}',
        ]);

        $pageRepository = $this->createMock(PageRepository::class);

        $page = (new Page('test-page'))
            ->setId(Uuid::uuid1())
            ->setTitle('')
            ->setContent('test edited');

        $url = new Url($page, 'test-link');
        $page->addUrl($url);

        $revision = (new Revision())
            ->setPage($page)
            ->setTitle('');

        $revisionRepository = $this->createMock(RevisionRepository::class);

        $revisionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($revision);

        $newRevision = new Revision();

        $revisionRepository->expects($this->once())
            ->method('hydrateNewRevisionFromPage')
            ->with($page)
            ->willReturn($newRevision);

        $user = new \Inachis\Entity\User\User();

        $this->controller->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with(
                'notice',
                $this->stringContains('Content reverted to version'),
            );

        $this->controller->expects($this->once())
            ->method('redirect')
            ->with('/incp/post/test-link')
            ->willReturn(new RedirectResponse('/incp/post/test-link'));

        $result = $this->controller->doRevert(
            $request,
            $pageRepository,
            $revisionRepository,
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/incp/post/test-link', $result->getTargetUrl());
    }

    /**
     * @throws Exception
     */
    public function testDownload(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/download/{id}',
        ]);

        $revisionRepository = $this->createMock(RevisionRepository::class);

        $page = new Page('', 'test');
        $page->setId(Uuid::uuid1());

        $revision = (new Revision())
            ->setPage($page)
            ->setTitle('')
            ->setContent('test');

        $revisionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($revision);

        $this->controller->expects($this->never())
            ->method('redirect');

        $result = $this->controller->download(
            $request,
            $revisionRepository,
        );

        $this->assertStringContainsString(
            'attachment; filename=',
            $result->headers->get('content-disposition'),
        );
    }

    /**
     * @throws Exception
     */
    public function testDownloadRevisionNotFound(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/download/{id}',
        ]);

        $revisionRepository = $this->createMock(RevisionRepository::class);

        $revisionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->controller->expects($this->never())
            ->method('redirect');

        $this->controller->download(
            $request,
            $revisionRepository,
        );
    }
}
