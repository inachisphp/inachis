<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Dashboard;

use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Controller\Page\Dashboard\DashboardController;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\ImageRepository;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardControllerTest extends InachisControllerTestCase
{
    /**
     * @throws Exception
     */
    public function testDefault(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/',
        ]);

        $controller = $this->getMockBuilder(DashboardController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(
                static function (string $template, array $data): Response {
                    return new Response('rendered:'.$template);
                },
            );

        $pageRepository = $this->createMock(PageRepository::class);

        $pageRepository->expects($this->once())
            ->method('findMostRecentlyEditedDraft')
            ->willReturn(null);

        $pageRepository->expects($this->once())
            ->method('findRecentDrafts')
            ->with(5)
            ->willReturn([]);

        $pageRepository->expects($this->once())
            ->method('findUpcoming')
            ->with(5)
            ->willReturn([]);

        $pageRepository->expects($this->once())
            ->method('findRecentPublished')
            ->with(5)
            ->willReturn([]);

        $pageRepository->expects($this->once())
            ->method('getDashboardCounts')
            ->willReturn([
                'drafts' => 0,
                'published' => 0,
                'upcoming' => 0,
            ]);

        $pageRepository->expects($this->once())
            ->method('getPagesWithoutTagsCount')
            ->willReturn(0);

        $pageRepository->expects($this->once())
            ->method('getPagesWithoutCategoriesCount')
            ->willReturn(0);

        $pageRepository->expects($this->once())
            ->method('getPagesWithoutFeatureImageCount')
            ->willReturn(0);

        $pageRepository->expects($this->once())
            ->method('getPagesWithoutFeatureSnippetCount')
            ->willReturn(0);

        $imageRepository = $this->createMock(ImageRepository::class);

        $imageRepository->expects($this->once())
            ->method('getImagesWithoutAltTextCount')
            ->willReturn(0);

        $seriesRepository = $this->createMock(SeriesRepository::class);

        $seriesRepository->expects($this->once())
            ->method('findRecentDrafts')
            ->with(5)
            ->willReturn([]);

        $seriesRepository->expects($this->once())
            ->method('findRecentPublished')
            ->with(5)
            ->willReturn([]);

        $analytics = $this->createMock(AnalyticsProviderInterface::class);

        $analytics->expects($this->once())
            ->method('getDashboardSummary')
            ->willReturn([]);

        $analytics->expects($this->once())
            ->method('getTopPages')
            ->with(
                $this->isInstanceOf(\DateTimeImmutable::class),
                $this->isInstanceOf(\DateTimeImmutable::class),
                5,
            )
            ->willReturn([]);

        $result = $controller->default(
            $analytics,
            $imageRepository,
            $pageRepository,
            $seriesRepository,
        );

        $this->assertSame(
            'rendered:inadmin/page/dashboard/dashboard.html.twig',
            $result->getContent(),
        );
    }
}
