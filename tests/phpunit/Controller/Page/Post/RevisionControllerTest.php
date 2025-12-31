<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Post;

use App\Controller\Page\Post\RevisionController;
use App\Entity\Page;
use App\Entity\Revision;
use App\Entity\Url;
use App\Repository\PageRepository;
use App\Repository\RevisionRepository;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Translation\Translator;

class RevisionControllerTest extends WebTestCase
{
    protected RevisionController $controller;

    protected function setUp(): void
    {
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(RevisionController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'getUser', 'redirect', 'render'])
            ->getMock();
    }

    public function testDiffEmptyRevision()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/page/diff/{id}'
        ]);
        $pageRepository = $this->createStub(PageRepository::class);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn(null);
        $this->controller->expects($this->never())->method('render');
        $this->expectException(NotFoundHttpException::class);

        $this->controller->diff($request, $pageRepository, $revisionRepository);
    }

    public function testDiffPageNotFound()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/page/diff/{id}'
        ]);
        $pageRepository = $this->createStub(PageRepository::class);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revision = new Revision();
        $revision->setPageId(1);
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn($revision);
        $this->controller->expects($this->never())->method('render');
        $this->expectException(NotFoundHttpException::class);

        $this->controller->diff($request, $pageRepository, $revisionRepository);
    }

    /**
     * @throws Exception
     */
    public function testDiff()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/page/diff/{id}'
        ]);
        $pageRepository = $this->createMock(PageRepository::class);
        $page = (new Page('test-page'))->setId(Uuid::uuid1())
            ->setTitle('')->setContent('teast edited');
        $url = new Url($page, 'test-link');
        $pageRepository->expects($this->once())->method('findOneBy')->willReturn($page);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revision = (new Revision())->setPageId(1)->setTitle('')->setContent('test');
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn($revision);
        $this->controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $this->controller->diff($request, $pageRepository, $revisionRepository);
    }

    /**
     * @throws Exception
     */
    public function testDoRevert()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/page/diff/{id}'
        ]);
        $pageRepository = $this->createMock(PageRepository::class);
        $page = (new Page('test-page'))->setId(Uuid::uuid1())
            ->setTitle('')->setContent('teast edited');
        $url = new Url($page, 'test-link');
        $pageRepository->expects($this->once())->method('findOneBy')->willReturn($page);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revision = (new Revision())->setPageId(1)->setTitle('')->setContent('test');
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn($revision);
        $this->controller->expects($this->once())
            ->method('redirect')
            ->willReturn(new RedirectResponse('/incc/post/'));
        $this->controller->doRevert($request, $pageRepository, $revisionRepository);
    }

    /**
     * @throws Exception
     */
    public function testDownload()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/page/download/{id}'
        ]);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revision = (new Revision())->setPageId(1)->setTitle('')->setContent('test');
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn($revision);
        $serializer = $this->createStub(SerializerInterface::class);
        $this->controller->expects($this->never())->method('redirect');

        $result = $this->controller->download($request, $revisionRepository, $serializer);
        $this->assertStringContainsString(
            'attachment; filename=',
            $result->headers->get('content-disposition')
        );
    }

    /**
     * @throws Exception
     */
    public function testDownloadRevisionNotFound()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/page/download/{id}'
        ]);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn(null);
        $serializer = $this->createStub(SerializerInterface::class);
        $this->expectException(NotFoundHttpException::class);
        $this->controller->expects($this->never())->method('redirect');

        $result = $this->controller->download($request, $revisionRepository, $serializer);
        $this->assertStringContainsString(
            'attachment; filename=',
            $result->headers->get('content-disposition')
        );
    }
}
