<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\API\Analytics;

use Doctrine\Common\Collections\ArrayCollection;
use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Controller\API\Analytics\ContentAnalyticsController;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Entity\Content\Url;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Tests\phpunit\Controller\AbstractInachisControllerTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentAnalyticsControllerTest extends AbstractInachisControllerTestCase
{
    public function testPostStatsReturnsEmptyResponseWhenPostNotFound(): void
    {
        $repository = $this->createMock(PageRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '123'])
            ->willReturn(null);

        $analytics = $this->createStub(AnalyticsProviderInterface::class);

        $controller = new ContentAnalyticsController(
            $this->entityManager,
            $this->params,
            $this->security,
            $this->translator,
            $this->wasteRepository,
            $this->pageViewFactory,
            $this->requestStack,
        );

        $response = $controller->postStats(
            new Request(),
            $analytics,
            $repository,
            '123',
        );

        $this->assertSame('', $response->getContent());
    }

    public function testPostStatsRendersAnalyticsTemplate(): void
    {
        $post = $this->createMock(Page::class);

        $url = $this->createMock(Url::class);
        $url->expects($this->atLeastOnce())->method('getLink')->willReturn('my-post');
        $urls = new ArrayCollection([$url]);
        $post->expects($this->atLeastOnce())->method('getUrls')->willReturn($urls);

        $repository = $this->createMock(PageRepository::class);
        $repository->expects($this->atLeastOnce())->method('findOneBy')->willReturn($post);

        $analytics = $this->createMock(AnalyticsProviderInterface::class);

        $analyticsData = [
            ['date' => '2025-01-01', 'views' => 10],
            ['date' => '2025-01-02', 'views' => 20],
        ];

        $analytics->expects($this->once())
            ->method('getPageStatsOverTime')
            ->willReturn($analyticsData);

        $analytics->expects($this->once())
            ->method('getTopReferrersForPage')
            ->with(
                '/my-post',
                $this->callback(fn (\DateTimeInterface $d) => true),
                $this->callback(fn (\DateTimeInterface $d) => true),
                10,
            )
            ->willReturn([]);

        $controller = $this->getMockBuilder(ContentAnalyticsController::class)
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
            ->with(
                'inadmin/partials/analytics.html.twig',
                $this->callback(function (array $vars) use ($post) {
                    return $vars['post'] === $post
                        && 30 === $vars['stats']['totalViews'];
                }),
            )
            ->willReturn(new Response('OK'));

        $response = $controller->postStats(
            new Request(),
            $analytics,
            $repository,
            '123',
        );

        $this->assertSame('OK', $response->getContent());
    }

    public function testPostStatsUsesProvidedDateRange(): void
    {
        $from = '2025-01-01';
        $to = '2025-01-31';

        $request = new Request([
            'from' => $from,
            'to' => $to,
        ]);

        $post = $this->createMock(Page::class);

        $url = $this->createMock(Url::class);
        $url->expects($this->atLeastOnce())->method('getLink')->willReturn('my-post');

        $post->expects($this->atLeastOnce())->method('getUrls')->willReturn(new ArrayCollection([$url]));

        $repository = $this->createMock(PageRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '123'])
            ->willReturn($post);

        $analytics = $this->createMock(AnalyticsProviderInterface::class);

        $analytics->expects($this->once())
            ->method('getPageStatsOverTime')
            ->with(
                $post,
                $this->callback(function (\DateTimeImmutable $date) {
                    return '2025-01-01' === $date->format('Y-m-d');
                }),
                $this->callback(function (\DateTimeImmutable $date) {
                    return '2025-01-31' === $date->format('Y-m-d');
                }),
            )
            ->willReturn([]);

        $analytics->expects($this->once())
            ->method('getTopReferrersForPage')
            ->with(
                '/my-post',
                $this->callback(
                    fn (\DateTimeImmutable $d) => '2025-01-01' === $d->format('Y-m-d'),
                ),
                $this->callback(
                    fn (\DateTimeImmutable $d) => '2025-01-31' === $d->format('Y-m-d'),
                ),
                10,
            );

        $controller = $this->getMockBuilder(ContentAnalyticsController::class)
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
            ->willReturn(new Response('OK'));

        $controller->postStats(
            $request,
            $analytics,
            $repository,
            '123',
        );
    }

    public function testSeriesStatsUsesProvidedDateRange(): void
    {
        $request = new Request([
            'from' => '2025-01-01',
            'to' => '2025-01-31',
        ]);

        $series = $this->createMock(Series::class);
        $series->expects($this->atLeastOnce())->method('getLastDate')->willReturn(new \DateTimeImmutable('2025-01-15'));
        $series->expects($this->atLeastOnce())->method('getUrl')->willReturn('test-series');

        $repository = $this->createMock(SeriesRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '123'])
            ->willReturn($series);

        $analytics = $this->createMock(AnalyticsProviderInterface::class);

        $analytics->expects($this->once())
            ->method('getSeriesStatsOverTime')
            ->with(
                $series,
                $this->callback(
                    fn (\DateTimeImmutable $d) => '2025-01-01' === $d->format('Y-m-d'),
                ),
                $this->callback(
                    fn (\DateTimeImmutable $d) => '2025-01-31' === $d->format('Y-m-d'),
                ),
            )
            ->willReturn([]);

        $analytics->expects($this->once())
            ->method('getTopReferrersForPage')
            ->with(
                '/2025/test-series',
                $this->callback(
                    fn (\DateTimeImmutable $d) => '2025-01-01' === $d->format('Y-m-d'),
                ),
                $this->callback(
                    fn (\DateTimeImmutable $d) => '2025-01-31' === $d->format('Y-m-d'),
                ),
                10,
            )
            ->willReturn([]);

        $controller = $this->getMockBuilder(ContentAnalyticsController::class)
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
            ->willReturn(new Response('OK'));

        $controller->seriesStats(
            $request,
            $analytics,
            $repository,
            '123',
        );
    }

    public function testSeriesStatsUsesDefaultDateRange(): void
    {
        $request = new Request();

        $series = $this->createMock(Series::class);
        $series->expects($this->atLeastOnce())->method('getLastDate')->willReturn(new \DateTimeImmutable('2025-01-15'));
        $series->expects($this->atLeastOnce())->method('getUrl')->willReturn('test-series');

        $repository = $this->createMock(SeriesRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '123'])
            ->willReturn($series);

        $analytics = $this->createMock(AnalyticsProviderInterface::class);
        $analytics->expects($this->once())
            ->method('getSeriesStatsOverTime')
            ->with(
                $series,
                $this->callback(function (\DateTimeImmutable $from) {
                    $days = (new \DateTimeImmutable())
                        ->diff($from)
                        ->days;

                    return $days >= 89 && $days <= 91;
                }),
                $this->callback(function (\DateTimeImmutable $to) {
                    return abs(
                        (new \DateTimeImmutable())->getTimestamp()
                        - $to->getTimestamp(),
                    ) < 5;
                }),
            )
            ->willReturn([]);

        $analytics->expects($this->once())
            ->method('getTopReferrersForPage')
            ->with(
                '/2025/test-series',
                $this->callback(function (\DateTimeImmutable $from) {
                    $days = (new \DateTimeImmutable())->diff($from)->days;

                    return $days >= 89 && $days <= 91;
                }),
                $this->callback(function (\DateTimeImmutable $to) {
                    return abs(
                        (new \DateTimeImmutable())->getTimestamp()
                        - $to->getTimestamp(),
                    ) < 5;
                }),
                10,
            )
            ->willReturn([]);

        $controller = $this->getMockBuilder(ContentAnalyticsController::class)
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
            ->willReturn(new Response('OK'));

        $controller->seriesStats(
            $request,
            $analytics,
            $repository,
            '123',
        );
    }

    public function testSeriesStatsReturnsEmptyResponseWhenSeriesNotFound(): void
    {
        $request = new Request();

        $repository = $this->createMock(SeriesRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '123'])
            ->willReturn(null);

        $analytics = $this->createMock(AnalyticsProviderInterface::class);
        $analytics->expects($this->never())->method('getSeriesStatsOverTime');
        $analytics->expects($this->never())->method('getTopReferrersForPage');

        $controller = new ContentAnalyticsController(
            $this->entityManager,
            $this->params,
            $this->security,
            $this->translator,
            $this->wasteRepository,
            $this->pageViewFactory,
            $this->requestStack,
        );

        $response = $controller->seriesStats(
            $request,
            $analytics,
            $repository,
            '123',
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('', $response->getContent());
    }
}
