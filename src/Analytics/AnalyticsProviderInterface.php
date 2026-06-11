<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Analytics;

use Inachis\Entity\Content\{Page, Series};

/**
 * Interface for analytics providers
 *
 * This interface is used to provide analytics data to the application.
 * It is a common interface for all analytics providers, such as Google Analytics
 */
interface AnalyticsProviderInterface
{
    /**
     * Get total page views per day between two dates.
     * 
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return list<array{date: string, total: numeric-string}>
     */
    public function getPageViewsPerDay(\DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Get most visited pages.
     * 
     * @param int $limit
     * @return list<array{path: string, total: numeric-string, title: string}>
     */
    public function getTopPages(int $limit = 10): array;

    /**
     * Get total views in a date range.
     * 
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return int
     */
    public function getTotalViews(\DateTimeInterface $from, \DateTimeInterface $to): int;

    /**
     * Get monthly unique visitor count
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return int
     */
    public function getMonthlyUniqueVisitors(\DateTimeInterface $from, \DateTimeInterface $to): int;

    /**
     * Get the most common paths that result in a 4xx or 5xx error.
     * 
     * @param int $limit
     * @return list<array{path: string, code: string, hits: numeric-string}>
     */
    public function getTopErrors(int $limit = 10): array;

    /**
     * Get trending pages
     *
     * @param int $limit
     * @return list<array{path: string, current: int, previous: int, change: float|int|null}>
     */
    public function getTrendingPages(int $limit = 10): array;

    /**
     * Get the most common referring domains.
     * 
     * @param int $limit
     * @return list<array{domain: string, total: numeric-string}>
     */
    public function getTopReferrers(int $limit = 10): array;

    /**
     * Get the most common referring domains for a specific page.
     * 
     * @param string $path
     * @param int $limit
     * @return list<array{domain: string, total: numeric-string}>
     */
    public function getTopReferrersForPage(string $path, int $limit = 10): array;

    /**
     * Get page views per day for paths
     *
     * @param string[] $paths
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return list<array{date: string, views: int}>
     */
    public function getPageViewsPerDayForPaths(array $paths, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Get page stats over time
     *
     * @param Page $page
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return list<array{date: string, views: int}>
     */
    public function getPageStatsOverTime(Page $page, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Get series stats over time
     *
     * @param Series $series
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return list<array{date: string, views: int}>
     */
    public function getSeriesStatsOverTime(Series $series, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Get top visitor countries/regions.
     * 
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int $limit
     * @return list<array{country_code: string, country_name: string, total: numeric-string}>
     */
    public function getTopRegions(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 10): array;

    /**
     * Get RSS subscriber stats over time.
     * 
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return list<array{date: string, subscribers: int}>
     */
    public function getSubscriberStatsOverTime(\DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Get current subscribers per feed path.
     * 
     * @return list<array{path: string, subscribers: numeric-string}>
     */
    public function getCurrentSubscribersPerFeed(): array;

    /**
     * Get top bot user-agents in the given date range.
     * 
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int $limit
     * @return list<array{user_agent: string, total: numeric-string}>
     */
    public function getTopBots(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 15): array;
}
