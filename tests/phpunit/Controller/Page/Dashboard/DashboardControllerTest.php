<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Dashboard;

use Doctrine\ORM\Tools\Pagination\Paginator;
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
        $controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:'.$template);
            });
        $paginator = $this->createStub(Paginator::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->atLeastOnce())->method('getAll')->willReturn($paginator);

        $analytics = $this->createMock(AnalyticsProviderInterface::class);
        $analytics->expects($this->once())->method('getTopPages')->willReturn([]);
        $analytics->expects($this->atLeastOnce())->method('getTotalViews')->willReturn(2);
        $analytics->expects($this->atLeastOnce())->method('getMonthlyUniqueVisitors')->willReturn(3);

        $result = $controller->default(
            $analytics,
            $this->createStub(ImageRepository::class),
            $pageRepository,
            $this->createStub(SeriesRepository::class),
        );
        $this->assertEquals(
            'rendered:inadmin/page/dashboard/dashboard.html.twig',
            $result->getContent(),
        );
    }
}
