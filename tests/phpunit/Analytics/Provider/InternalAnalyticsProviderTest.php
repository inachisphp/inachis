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
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Content\UrlRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class InternalAnalyticsProviderTest extends TestCase
{
    private AnalyticsRepository $analyticsRepository;
    private SeriesRepository $seriesRepository;
    private UrlRepository $urlRepository;

    protected function setUp(): void
    {
        $this->analyticsRepository = $this->createMock(AnalyticsRepository::class);
        $this->seriesRepository = $this->createStub(SeriesRepository::class);
        $this->urlRepository = $this->createStub(UrlRepository::class);
    }

    private function createCache(): CacheInterface
    {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->method('expiresAfter')
            ->willReturnSelf();

        $cache = $this->createMock(CacheInterface::class);

        $cache
            ->method('get')
            ->willReturnCallback(
                static function (
                    string $key,
                    callable $callback
                ) use ($item) {
                    return $callback($item);
                }
            );

        return $cache;
    }

    private function createProvider(): InternalAnalyticsProvider
    {
        return new InternalAnalyticsProvider(
            $this->analyticsRepository,
            $this->createCache(),
            $this->seriesRepository,
            $this->urlRepository,
        );
    }

    public function testGetTopPages(): void
    {
        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTopPages')
            ->willReturn([
                ['path' => '/', 'total' => 10],
                ['path' => '/test', 'total' => 3],
            ]);

        $provider = $this->createProvider();

        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $result = $provider->getTopPages($from, $to, 2);

        $this->assertSame([
            ['path' => '/', 'total' => 10, 'title' => 'Home'],
            ['path' => '/test', 'total' => 3, 'title' => '/test'],
        ], $result);
    }

    public function testGetPageViewsPerDay(): void
    {
        $from = new DateTimeImmutable('2026-04-29');
        $to = new DateTimeImmutable('2026-04-30');

        $expected = [
            ['date' => '2026-04-29', 'total' => 123],
            ['date' => '2026-04-30', 'total' => 73],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getPageViewsPerDay')
            ->with($from, $to)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getPageViewsPerDay($from, $to);

        $this->assertSame($expected, $result);
    }

    public function testGetTotalViews(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTotalViews')
            ->with($from, $to)
            ->willReturn(123);

        $result = $this->createProvider()
            ->getTotalViews($from, $to);

        $this->assertSame(123, $result);
    }

    public function testGetMonthlyUniqueVisitors(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getMonthlyUniqueVisitors')
            ->with($from, $to)
            ->willReturn(456);

        $result = $this->createProvider()
            ->getMonthlyUniqueVisitors($from, $to);

        $this->assertSame(456, $result);
    }

    public function testGetTopErrors(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['path' => '/', 'code' => 404, 'hits' => 5],
            ['path' => '/test', 'code' => 500, 'hits' => 2],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTopErrors')
            ->with($from, $to, 10)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getTopErrors($from, $to, 10);

        $this->assertSame($expected, $result);
    }

    public function testGetTrendingPages(): void
    {
        $from = new DateTimeImmutable('2026-05-01');
        $to = new DateTimeImmutable('2026-05-31');

        $previousFrom = new DateTimeImmutable('2026-04-01');
        $previousTo = new DateTimeImmutable('2026-04-30');

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTrendingPages')
            ->with($from, $to, $previousFrom, $previousTo, 10)
            ->willReturn([
                ['path' => '/', 'current' => 7, 'previous' => 4, 'change' => 75],
                ['path' => '/2026-test-series', 'current' => 5, 'previous' => 1, 'change' => 400],
                ['path' => '/tag/half-marathon', 'current' => 4, 'previous' => 2, 'change' => 100],
                ['path' => '/2026/04/25/test-content', 'current' => 3, 'previous' => 1, 'change' => 200],
                ['path' => '/category/running', 'current' => 2, 'previous' => 1, 'change' => 100],
                ['path' => '/test', 'current' => 2, 'previous' => 2, 'change' => 0],
                ['path' => '/author/john-doe', 'current' => 1, 'previous' => 0, 'change' => null],
            ]);

        $page = new Page('Test Content');
        $url = new Url($page, '2026/04/25/test-content', true);

        $this->urlRepository = $this->createMock(UrlRepository::class);
        $this->urlRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($url);

        $series = (new Series())
            ->setTitle('Test Series');

        $this->seriesRepository = $this->createMock(SeriesRepository::class);
        $this->seriesRepository
            ->expects($this->once())
            ->method('getPublicSeriesByYearAndUrl')
            ->willReturn($series);

        $result = $this->createProvider()->getTrendingPages(
            $from,
            $to,
            $previousFrom,
            $previousTo,
            10
        );

        $this->assertSame([
            ['path' => '/', 'current' => 7, 'previous' => 4, 'change' => 75, 'title' => 'Home'],
            ['path' => '/2026-test-series', 'current' => 5, 'previous' => 1, 'change' => 400, 'title' => 'Test Series'],
            ['path' => '/tag/half-marathon', 'current' => 4, 'previous' => 2, 'change' => 100, 'title' => 'Tag: half-marathon'],
            ['path' => '/2026/04/25/test-content', 'current' => 3, 'previous' => 1, 'change' => 200, 'title' => 'Test Content'],
            ['path' => '/category/running', 'current' => 2, 'previous' => 1, 'change' => 100, 'title' => 'Category: running'],
            ['path' => '/test', 'current' => 2, 'previous' => 2, 'change' => 0, 'title' => '/test'],
            ['path' => '/author/john-doe', 'current' => 1, 'previous' => 0, 'change' => null, 'title' => 'Author: john-doe'],
        ], $result);
    }

        public function testGetTopReferrers(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['domain' => 'Direct', 'total' => 10],
            ['domain' => 'DuckDuckGo', 'total' => 3],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTopReferrers')
            ->with($from, $to, 10)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getTopReferrers($from, $to, 10);

        $this->assertSame($expected, $result);
    }

    public function testGetTopReferrersForPage(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['domain' => 'Direct', 'total' => 10],
            ['domain' => 'DuckDuckGo', 'total' => 3],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTopReferrersForPage')
            ->with('/', $from, $to, 10)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getTopReferrersForPage('/', $from, $to, 10);

        $this->assertSame($expected, $result);
    }

    public function testGetPageViewsPerDayForPaths(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['date' => '2026-01-01', 'views' => 5],
            ['date' => '2026-01-02', 'views' => 7],
        ];

        $paths = ['/', '/test'];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getPageViewsPerDayForPaths')
            ->with($paths, $from, $to)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getPageViewsPerDayForPaths($paths, $from, $to);

        $this->assertSame($expected, $result);
    }

    public function testGetPageStatsOverTime(): void
    {
        $page = new Page('Test Page');

        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['date' => '2026-01-01', 'views' => 10],
            ['date' => '2026-01-02', 'views' => 12],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getPageStatsOverTime')
            ->with($page, $from, $to)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getPageStatsOverTime($page, $from, $to);

        $this->assertSame($expected, $result);
    }

    public function testGetSeriesStatsOverTime(): void
    {
        $series = new Series();

        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['date' => '2026-01-01', 'views' => 20],
            ['date' => '2026-01-02', 'views' => 25],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getSeriesStatsOverTime')
            ->with($series, $from, $to)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getSeriesStatsOverTime($series, $from, $to);

        $this->assertSame($expected, $result);
    }

    public function testGetTopRegions(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            [
                'country_code' => 'GB',
                'country_name' => 'United Kingdom',
                'total' => '42',
            ],
            [
                'country_code' => 'US',
                'country_name' => 'United States',
                'total' => '12',
            ],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTopRegions')
            ->with($from, $to, 10)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getTopRegions($from, $to, 10);

        $this->assertSame($expected, $result);
    }

    public function testGetSubscriberStatsOverTime(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['date' => '2026-01-01', 'subscribers' => 100],
            ['date' => '2026-01-02', 'subscribers' => 101],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getSubscriberStatsOverTime')
            ->with($from, $to)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getSubscriberStatsOverTime($from, $to);

        $this->assertSame($expected, $result);
    }

    public function testGetCurrentSubscribersPerFeed(): void
    {
        $expected = [
            [
                'path' => '/feed.xml',
                'subscribers' => '150',
            ],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getCurrentSubscribersPerFeed')
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getCurrentSubscribersPerFeed();

        $this->assertSame($expected, $result);
    }

    public function testGetTopBots(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            [
                'user_agent' => 'Googlebot',
                'total' => '50',
            ],
            [
                'user_agent' => 'Bingbot',
                'total' => '12',
            ],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTopBots')
            ->with($from, $to, 15)
            ->willReturn($expected);

        $result = $this->createProvider()
            ->getTopBots($from, $to);

        $this->assertSame($expected, $result);
    }

        public function testGetDashboardSummary(): void
    {
        $expected = [
            'viewsToday' => 123,
            'viewsYesterday' => 98,
            'viewsThisMonth' => 1234,
            'viewsLastMonth' => 1100,
            'uniqueVisitorsThisMonth' => 456,
            'uniqueVisitorsLastMonth' => 432,
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getDashboardSummary')
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getDashboardSummary()
        );
    }

    public function testGetTotalErrors(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTotalErrors')
            ->with($from, $to)
            ->willReturn(42);

        $this->assertSame(
            42,
            $this->createProvider()->getTotalErrors($from, $to)
        );
    }

    public function testGetSecuritySummary(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            'total' => 100,
            'uniqueIps' => 20,
            'high' => 12,
            'critical' => 2,
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getSecuritySummary')
            ->with($from, $to)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getSecuritySummary($from, $to)
        );
    }

    public function testGetTopSecurityPaths(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['path' => '/wp-login.php', 'total' => '45'],
            ['path' => '/xmlrpc.php', 'total' => '12'],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTopSecurityPaths')
            ->with($from, $to, 10)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getTopSecurityPaths($from, $to)
        );
    }

    public function testGetTopSecurityTypes(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['type' => 'sql_injection', 'total' => '18'],
            ['type' => 'xss', 'total' => '6'],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTopSecurityTypes')
            ->with($from, $to, 10)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getTopSecurityTypes($from, $to)
        );
    }

    public function testGetTopSecurityIps(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['ip' => '192.0.2.1', 'total' => '20'],
            ['ip' => '198.51.100.10', 'total' => '8'],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getTopSecurityIps')
            ->with($from, $to, 10)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getTopSecurityIps($from, $to)
        );
    }

    public function testGetSecurityEventsPerDay(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['date' => '2026-01-01', 'total' => '8'],
            ['date' => '2026-01-02', 'total' => '15'],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getSecurityEventsPerDay')
            ->with($from, $to)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getSecurityEventsPerDay($from, $to)
        );
    }

    public function testGetRecentSecurityEvents(): void
    {
        $expected = [
            [
                'date' => '2026-01-01 12:00:00',
                'type' => 'sql_injection',
                'severity' => 5,
                'path' => '/login',
                'ip' => '192.0.2.1',
                'hits' => 10,
            ],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getRecentSecurityEvents')
            ->with(20)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getRecentSecurityEvents()
        );
    }

    public function testGetCriticalSecurityEvents(): void
    {
        $expected = [
            [
                'date' => '2026-01-01 12:00:00',
                'type' => 'remote_code_execution',
                'severity' => 10,
                'path' => '/admin',
                'ip' => '192.0.2.2',
                'hits' => 5,
            ],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getCriticalSecurityEvents')
            ->with(10)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getCriticalSecurityEvents()
        );
    }

    public function testGetSecurityMethods(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['method' => 'GET', 'total' => '120'],
            ['method' => 'POST', 'total' => '35'],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getSecurityMethods')
            ->with($from, $to)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getSecurityMethods($from, $to)
        );
    }

    public function testGetSecurityEventsByType(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-01-31');

        $expected = [
            ['type' => 'sql_injection', 'total' => '15'],
            ['type' => 'xss', 'total' => '9'],
        ];

        $this->analyticsRepository
            ->expects($this->once())
            ->method('getSecurityEventsByType')
            ->with($from, $to, 10)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->createProvider()->getSecurityEventsByType($from, $to)
        );
    }
}
