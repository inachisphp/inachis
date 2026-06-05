<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Analytics\Provider;

use DateTimeImmutable;
use Inachis\Analytics\Provider\InternalAnalyticsProvider;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Entity\Content\Url;
use Inachis\Repository\Analytics\AnalyticsRepository;
use Inachis\Repository\Content\{PageRepository, SeriesRepository, UrlRepository};
use PHPUnit\Framework\TestCase;

class InternalAnalyticsProviderTest extends TestCase
{
    public function testGetTopPages(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getTopPages')->willReturn([
            ['path' => '/', 'total' => 10],
            ['path' => '/test', 'total' => 3],
        ]);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );

        $result = $analyticsProvider->getTopPages();
        $this->assertEquals([
            ['path' => '/', 'total' => 10, 'title' => 'Home'],
            ['path' => '/test', 'total' => 3, 'title' => '/test'],
        ], $result);
    }

    public function testGetPageViewsPerDay(): void
    {
        $now = new DateTimeImmutable();
        $pageViews = [
            ['date' => '2026-04-29', 'total' => 123],
            ['date' => '2026-04-30', 'total' => 73],
        ];
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getPageViewsPerDay')->willReturn($pageViews);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );

        $result = $analyticsProvider->getPageViewsPerDay($now, $now);
        $this->assertEquals($pageViews, $result);
    }

    public function testGetTotalViews(): void
    {
        $now = new DateTimeImmutable();
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getTotalViews')->willReturn(123);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );

        $result = $analyticsProvider->getTotalViews($now, $now);
        $this->assertEquals(123, $result);
    }

    public function testGetMonthlyUniqueVisitors(): void
    {
        $now = new DateTimeImmutable();
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getMonthlyUniqueVisitors')->willReturn(123);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );

        $result = $analyticsProvider->getMonthlyUniqueVisitors($now, $now);
        $this->assertEquals(123, $result);
    }

    public function testGetTopErrors(): void
    {
        $pageViews = [
            ['path' => '/', 'code' => 404, 'hits' => 5],
            ['path' => '/test', 'code' => 500, 'hits' => 2],
        ];
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getTopErrors')->willReturn($pageViews);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );

        $result = $analyticsProvider->getTopErrors(10);
        $this->assertEquals($pageViews, $result);
    }

    public function testGetTrendingPages(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getTrendingPages')->willReturn([
            ['path' => '/', 'hits' => 7],
            ['path' => '/2026-test-series', 'hits' => '5'],
            ['path' => '/tag/half-marathon', 'hits' => 4],
            ['path' => '/2026/04/25/test-content', 'hits' => 3],
            ['path' => '/category/running', 'hits' => 2],
            ['path' => '/test', 'hits' => 2],
            ['path' => '/author/john-doe', 'hits' => 1],
        ]);

        $page = new Page('Test Content');
        $url = new Url($page, '/2026/04/25/test-content', true);
        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->expects($this->once())->method('findOneBy')->willReturn($url);

        $series = (new Series())->setTitle('Test Series');
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())->method('getPublicSeriesByYearAndUrl')->willReturn($series);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $seriesRepository,
            $urlRepository,
        );

        $result = $analyticsProvider->getTrendingPages(10);
        $this->assertEquals([
            ['path' => '/', 'hits' => 7, 'title' => 'Home'],
            ['path' => '/2026-test-series', 'hits' => '5', 'title' => 'Test Series'],
            ['path' => '/tag/half-marathon', 'hits' => 4, 'title' => 'Tag: half-marathon'],
            ['path' => '/2026/04/25/test-content', 'hits' => 3, 'title' => 'Test Content'],
            ['path' => '/category/running', 'hits' => 2, 'title' => 'Category: running'],
            ['path' => '/test', 'hits' => 2, 'title' => '/test'],
            ['path' => '/author/john-doe', 'hits' => 1, 'title' => 'Author: john-doe'],
        ], $result);
    }

    public function testGetTopReferrers(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getTopReferrers')->willReturn([
            ['path' => 'Direct', 'total' => 10],
            ['path' => 'Duck Duck Go', 'total' => 3],
        ]);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );

        $result = $analyticsProvider->getTopReferrers(10);
        $this->assertEquals([
            ['path' => 'Direct', 'total' => 10],
            ['path' => 'Duck Duck Go', 'total' => 3],
        ], $result);
    }

    public function testGetTopReferrersForPage(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getTopReferrersForPage')->willReturn([
            ['path' => 'Direct', 'total' => 10],
            ['path' => 'Duck Duck Go', 'total' => 3],
        ]);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );

        $result = $analyticsProvider->getTopReferrersForPage('/', 10);
        $this->assertEquals([
            ['path' => 'Direct', 'total' => 10],
            ['path' => 'Duck Duck Go', 'total' => 3],
        ], $result);
    }

    public function testGetPageViewsPerDayForPaths(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getPageViewsPerDayForPaths')->willReturn([]);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );
        $testDate = new DateTimeImmutable('now');

        $result = $analyticsProvider->getPageViewsPerDayForPaths([ '/', '/test'], $testDate, $testDate);
        $this->assertEquals([], $result);
    }

    public function testGetPageStatsOverTime(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getPageStatsOverTime')->willReturn([
        ]);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );
        $testDate = new DateTimeImmutable('now');

        $result = $analyticsProvider->getPageStatsOverTime(new Page(), $testDate, $testDate);
        $this->assertEquals([], $result);        
    }

    public function testGetSeriesStatsOverTime(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getSeriesStatsOverTime')->willReturn([
        ]);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );
        $testDate = new DateTimeImmutable('now');

        $result = $analyticsProvider->getSeriesStatsOverTime(new Series(), $testDate, $testDate);
        $this->assertEquals([], $result);      
    }

    public function testGetTopRegions(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getTopRegions')->willReturn([
        ]);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );
        $testDate = new DateTimeImmutable('now');

        $result = $analyticsProvider->getTopRegions($testDate, $testDate, 10);
        $this->assertEquals([], $result);   
    }

    public function testGetSubscriberStatsOverTime(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getSubscriberStatsOverTime')->willReturn([
        ]);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );
        $testDate = new DateTimeImmutable('now');

        $result = $analyticsProvider->getSubscriberStatsOverTime($testDate, $testDate);
        $this->assertEquals([], $result);   
    }

    public function testGetCurrentSubscribersPerFeed(): void
    {
        $analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $analyticsRepository->expects($this->once())->method('getCurrentSubscribersPerFeed')->willReturn([
        ]);

        $analyticsProvider = new InternalAnalyticsProvider(
            $analyticsRepository,
            $this->createStub(SeriesRepository::class),
            $this->createStub(UrlRepository::class)
        );

        $result = $analyticsProvider->getCurrentSubscribersPerFeed();
        $this->assertEquals([], $result);   
    }
}
