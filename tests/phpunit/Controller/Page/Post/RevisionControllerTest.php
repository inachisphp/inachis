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
            ->onlyMethods(['addFlash', 'getUser', 'redirect', 'render'])
            ->getMock();
    }

    public function testDiffEmptyRevision()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/diff/{id}',
        ]);
        $revisionDiff = $this->createStub(RevisionDiffRenderer::class);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn(null);
        $this->controller->expects($this->never())->method('render');
        $this->expectException(NotFoundHttpException::class);

        $this->controller->diff($request, $revisionDiff, $revisionRepository);
    }

    public function testDiffPageNotFound()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/diff/{id}',
        ]);
        $revisionDiff = $this->createStub(RevisionDiffRenderer::class);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revision = new Revision();
        $page = new Page();
        $page->setId(Uuid::uuid1());
        $revision->setPage($page);
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn($revision);
        $this->controller->expects($this->never())->method('render');
        $this->expectException(RuntimeException::class);

        $this->controller->diff($request, $revisionDiff, $revisionRepository);
    }

    /**
     * @throws Exception
     */
    public function testDiff()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/diff/{id}',
        ]);
        $page = (new Page('test-page'))->setId(Uuid::uuid1())
            ->setTitle('')->setContent('teast edited');
        $url = new Url($page, 'test-link');
        $revisionDiff = $this->createStub(RevisionDiffRenderer::class);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $page = new Page('', 'test');
        $page->setId(Uuid::uuid1());
        $page->addUrl($url);
        $revision = (new Revision())->setPage($page)->setTitle('');
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn($revision);
        $this->controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:'.$template);
            });
        $this->controller->diff($request, $revisionDiff, $revisionRepository);
    }

    /**
     * @throws Exception
     */
    public function testDoRevert()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/diff/{id}',
        ]);
        $pageRepository = $this->createMock(PageRepository::class);
        $page = (new Page('test-page'))->setId(Uuid::uuid1())
            ->setTitle('')->setContent('teast edited');
        $url = new Url($page, 'test-link');
        $pageRepository->expects($this->once())->method('findOneBy')->willReturn($page);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $page = new Page('', 'test');
        $page->setId(Uuid::uuid1());
        $page->addUrl($url);
        $revision = (new Revision())->setPage($page)->setTitle('');
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn($revision);
        $this->controller->expects($this->once())
            ->method('redirect')
            ->willReturn(new RedirectResponse('/incp/post/'));
        $this->controller->doRevert($request, $pageRepository, $revisionRepository);
    }

    /**
     * @throws Exception
     */
    public function testDownload()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/download/{id}',
        ]);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $page = new Page('', 'test');
        $page->setId(Uuid::uuid1());
        $revision = (new Revision())->setPage($page)->setTitle('')->setContent('test');
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn($revision);
        $this->controller->expects($this->never())->method('redirect');

        $result = $this->controller->download($request, $revisionRepository);
        $this->assertStringContainsString(
            'attachment; filename=',
            $result->headers->get('content-disposition'),
        );
    }

    /**
     * @throws Exception
     */
    public function testDownloadRevisionNotFound()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/page/download/{id}',
        ]);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn(null);
        $this->expectException(NotFoundHttpException::class);
        $this->controller->expects($this->never())->method('redirect');

        $result = $this->controller->download($request, $revisionRepository);
        $this->assertStringContainsString(
            'attachment; filename=',
            $result->headers->get('content-disposition'),
        );
    }
}
