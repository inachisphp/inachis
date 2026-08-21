<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Analytics\Provider;

use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Repository\Analytics\AnalyticsRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Content\UrlRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Internal analytics provider.
 *
 * This provider is used to track analytics data in the internal database
 */
class InternalAnalyticsProvider implements AnalyticsProviderInterface
{
    public function __construct(
        private AnalyticsRepository $analyticsRepository,
        private CacheInterface $cache,
        private SeriesRepository $seriesRepository,
        private UrlRepository $urlRepository,
    ) {
    }

    /**
     * Get top pages.
     *
     * @return list<array{path: string, total: numeric-string, title: string}>
     */
    public function getTopPages(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 10,
    ): array {
        /** @var list<array{path: string, total: numeric-string, title: string}> */
        return $this->cache->get(
            'analytics_top_pages_'.$limit,
            function (ItemInterface $item) use ($from, $to, $limit) {
                $item->expiresAfter(600);

                $rows = $this->analyticsRepository->getTopPages($from, $to, $limit);

                return array_map(function (array $row): array {
                    return [
                        'path' => $row['path'],
                        'total' => $row['total'],
                        'title' => $this->resolveTitle($row['path']),
                    ];
                }, $rows);
            },
        );
    }

    /**
     * Get page views per day.
     *
     * @return list<array{date: string, total: numeric-string}>
     */
    public function getPageViewsPerDay(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        return $this->analyticsRepository->getPageViewsPerDay($from, $to);
    }

    /**
     * Get total views.
     */
    public function getTotalViews(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->analyticsRepository->getTotalViews($from, $to);
    }

    /**
     * Get monthly unique visitors.
     */
    public function getMonthlyUniqueVisitors(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->analyticsRepository->getMonthlyUniqueVisitors($from, $to);
    }

    /**
     * Get the most common paths that result in a 4xx or 5xx error.
     *
     * @return list<array{path: string, code: string, hits: numeric-string}>
     */
    public function getTopErrors(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 10): array
    {
        return $this->analyticsRepository->getTopErrors($from, $to, $limit);
    }

    /**
     * Get trending pages.
     *
     * @return list<array{path: string, current: int, previous: int, change: float|int|null}>
     */
    public function getTrendingPages(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        \DateTimeInterface $previousFrom,
        \DateTimeInterface $previousTo,
        int $limit = 10,
    ): array {
        return array_map(function ($row) {
            $row['title'] = $this->resolveTitle($row['path']);

            return $row;
        }, $this->analyticsRepository->getTrendingPages($from, $to, $previousFrom, $previousTo, $limit));
    }

    /**
     * Get the most common referring domains.
     *
     * @return list<array{domain: string, total: numeric-string}>
     */
    public function getTopReferrers(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 10): array
    {
        return $this->analyticsRepository->getTopReferrers($from, $to, $limit);
    }

    /**
     * Get the most common referring domains for a specific page.
     *
     * @return list<array{domain: string, total: numeric-string}>
     */
    public function getTopReferrersForPage(string $path, \DateTimeInterface $from, \DateTimeInterface $to, int $limit = 10): array
    {
        return $this->analyticsRepository->getTopReferrersForPage($path, $from, $to, $limit);
    }

    /**
     * Resolve title.
     */
    private function resolveTitle(string $path): string
    {
        if ('/' === $path || empty($path)) {
            return 'Home';
        }
        if (preg_match('#^/[\d]{4}/[/\d]{2}/[/\d]{2}/(.+)$#', $path, $matches)) {
            $slug = ltrim($matches[0], '/');
            $url = $this->urlRepository->findOneBy([
                'link' => $slug,
            ]);
            $content = $url?->getContent();

            return $content
                ? $content->getTitle().($content->getSubTitle() ? ' - '.$content->getSubTitle() : '')
                : $path;
        }
        if (preg_match('#/([\d]{4})\-(.+)$#', $path, $matches)) {
            $year = $matches[1];
            $title = $matches[2];
            $series = $this->seriesRepository->getPublicSeriesByYearAndUrl(
                $year,
                $title,
            );

            return $series
                ? $series->getTitle().($series->getSubTitle() ? ' - '.$series->getSubTitle() : '')
                : $path;
        }

        if (preg_match('#^/tag/(.+)$#', $path, $matches)) {
            $tag = $matches[1];

            return 'Tag: '.$tag;
        }

        if (preg_match('#^/category/(.+)$#', $path, $matches)) {
            $category = $matches[1];

            return 'Category: '.$category;
        }

        if (preg_match('#^/author/(.+)$#', $path, $matches)) {
            $author = $matches[1];

            return 'Author: '.$author;
        }

        return $path;
    }

    /**
     * Get page views per day for paths.
     *
     * @param string[] $paths
     *
     * @return list<array{date: string, views: int}>
     */
    public function getPageViewsPerDayForPaths(
        array $paths,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        return $this->analyticsRepository->getPageViewsPerDayForPaths($paths, $from, $to);
    }

    /**
     * Get page stats over time.
     *
     * @return list<array{date: string, views: int}>
     */
    public function getPageStatsOverTime(Page $page, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->analyticsRepository->getPageStatsOverTime($page, $from, $to);
    }

    /**
     * Get series stats over time.
     *
     * @return list<array{date: string, views: int}>
     */
    public function getSeriesStatsOverTime(Series $series, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->analyticsRepository->getSeriesStatsOverTime($series, $from, $to);
    }

    /**
     * Get top visitor countries/regions.
     *
     * @return list<array{country_code: string, country_name: string, total: numeric-string}>
     */
    public function getTopRegions(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 10): array
    {
        return $this->analyticsRepository->getTopRegions($from, $to, $limit);
    }

    /**
     * Get RSS subscriber stats over time.
     *
     * @return list<array{date: string, subscribers: int}>
     */
    public function getSubscriberStatsOverTime(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->analyticsRepository->getSubscriberStatsOverTime($from, $to);
    }

    /**
     * Get current subscribers per feed path.
     *
     * @return list<array{path: string, subscribers: numeric-string}>
     */
    public function getCurrentSubscribersPerFeed(): array
    {
        return $this->analyticsRepository->getCurrentSubscribersPerFeed();
    }

    /**
     * Get top bot user-agents in the given date range.
     *
     * @return list<array{user_agent: string, total: numeric-string}>
     */
    public function getTopBots(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 15): array
    {
        return $this->analyticsRepository->getTopBots($from, $to, $limit);
    }

    /**
     * Returns an associative array of views today, yesterday, this month and last month
     * along with unique visitors this month and last month.
     *
     * @return array{
     *     viewsToday: int,
     *     viewsYesterday: int,
     *     viewsThisMonth: int,
     *     viewsLastMonth: int,
     *     uniqueVisitorsThisMonth: int,
     *     uniqueVisitorsLastMonth: int
     * }
     */
    public function getDashboardSummary(): array
    {
        return $this->analyticsRepository->getDashboardSummary();
    }

    /**
     * Total number of 4xx/5xx responses.
     */
    public function getTotalErrors(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): int {
        return $this->analyticsRepository->getTotalErrors($from, $to);
    }

    /**
     * Get security dashboard summary.
     *
     * @return array{
     *     total:int,
     *     uniqueIps:int,
     *     high:int,
     *     critical:int
     * }
     */
    public function getSecuritySummary(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        return $this->analyticsRepository->getSecuritySummary($from, $to);
    }

    /**
     * Get most targeted paths.
     *
     * @return list<array{path:string,total:numeric-string}>
     */
    public function getTopSecurityPaths(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 10,
    ): array {
        return $this->analyticsRepository->getTopSecurityPaths($from, $to, $limit);
    }

    /**
     * Get most common security event types.
     *
     * @return list<array{type:string,total:numeric-string}>
     */
    public function getTopSecurityTypes(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 10,
    ): array {
        return $this->analyticsRepository->getTopSecurityTypes($from, $to, $limit);
    }

    /**
     * Get IP addresses generating the most security events.
     *
     * @return list<array{ip:string,total:numeric-string}>
     */
    public function getTopSecurityIps(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 10,
    ): array {
        return $this->analyticsRepository->getTopSecurityIps($from, $to, $limit);
    }

    /**
     * Get security events over time.
     *
     * @return list<array{date:string,total:numeric-string}>
     */
    public function getSecurityEventsPerDay(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        return $this->analyticsRepository->getSecurityEventsPerDay($from, $to);
    }

    /**
     * Get recent security events.
     *
     * @return list<array{
     *     date:string,
     *     type:string,
     *     severity:int,
     *     path:string,
     *     ip:string,
     *     hits:int
     * }>
     */
    public function getRecentSecurityEvents(
        int $limit = 20,
    ): array {
        return $this->analyticsRepository->getRecentSecurityEvents($limit);
    }

    /**
     * Get highest severity events.
     *
     * Useful for an "active threats" panel.
     *
     * @return list<array{
     *     date:string,
     *     type:string,
     *     severity:int,
     *     path:string,
     *     ip:string,
     *     hits:int
     * }>
     */
    public function getCriticalSecurityEvents(
        int $limit = 10,
    ): array {
        return $this->analyticsRepository->getCriticalSecurityEvents($limit);
    }

    /**
     * Get security activity by HTTP method.
     *
     * @return list<array{method:string,total:numeric-string}>
     */
    public function getSecurityMethods(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        return $this->analyticsRepository->getSecurityMethods($from, $to);
    }

    /**
     * Get security events grouped by type.
     *
     * @return list<array{type:string,total:numeric-string}>
     */
    public function getSecurityEventsByType(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 10,
    ): array {
        return $this->analyticsRepository->getSecurityEventsByType($from, $to, $limit);
    }
}
