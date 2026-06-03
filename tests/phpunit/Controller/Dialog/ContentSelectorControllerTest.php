<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Dialog;

use Inachis\Controller\Dialog\ContentSelectorController;
use Inachis\Entity\Content\{Page, Series};
use Inachis\Repository\PageRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Nonstandard\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSelectorControllerTest extends InachisControllerTestCase
{
    /**
     * @throws Exception
     */
    public function testContentList(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'seriesId' => $uuid->toString(),
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/contentSelector/get'
        ]);
        $controller = $this->getMockBuilder(ContentSelectorController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $series = (new Series())->setId(Uuid::uuid1());
        $series->addItem(new Page());
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())->method('find')->willReturn($series);
        $pageRepository = $this->createStub(PageRepository::class);

        $result = $controller->contentList($request, $seriesRepository, $pageRepository);
        $this->assertEquals('rendered:inadmin/dialog/content-selector.html.twig', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testSaveContentNoChange(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'seriesId' => $uuid->toString(),
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/contentSelector/save'
        ]);
        $controller = $this->getMockBuilder(ContentSelectorController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->never())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $seriesRepository = $this->createStub(SeriesRepository::class);
        $pageRepository = $this->createStub(PageRepository::class);

        $result = $controller->saveContent($request, $seriesRepository, $pageRepository);
        $this->assertEquals('No change', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testSaveContentNoPagesAdded(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'ids' => [
                $uuid->toString(),
            ],
            'seriesId' => $uuid->toString(),
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/contentSelector/save'
        ]);
        $controller = $this->getMockBuilder(ContentSelectorController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->never())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $series = (new Series())->setId(Uuid::uuid1());
        $series->addItem(new Page());
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())->method('findOneBy')->willReturn($series);
        $pageRepository = $this->createStub(PageRepository::class);

        $result = $controller->saveContent($request, $seriesRepository, $pageRepository);
        $this->assertEquals('Saved', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testSaveContent(): void
    {
        $uuid = Uuid::uuid1();
        $uuid2 = Uuid::uuid1();
        $request = new Request([], [
            'ids' => [
                $uuid2->toString(),
            ],
            'seriesId' => $uuid->toString(),
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/contentSelector/save'
        ]);
        $controller = $this->getMockBuilder(ContentSelectorController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->never())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $page = (new Page('test-page'))->setId($uuid2);
        $series = (new Series())->setId(Uuid::uuid1());
        $series->addItem($page);
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())->method('findOneBy')->willReturn($series);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())->method('findOneBy')->willReturn($page);

        $result = $controller->saveContent($request, $seriesRepository, $pageRepository);
        $this->assertEquals('Saved', $result->getContent());
    }
}
