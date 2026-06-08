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
     * Expected format:
     * [
     *   ['date' => '2026-04-29', 'total' => 123],
     *   ...
     * ]
     * 
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array<array{date: string, total: int}>
     */
    public function getPageViewsPerDay(
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array;

    /**
     * Get most visited pages.
     *
     * Expected format:
     * [
     *   ['path' => '/post/hello-world', 'total' => 42],
     *   ...
     * ]
     * 
     * @param int $limit
     * @return array<array{path: string, total: int}>
     */
    public function getTopPages(int $limit = 10): array;

    /**
     * Get total views in a date range.
     * 
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return int
     */
    public function getTotalViews(
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): int;

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
     * Expected format:
     * [
     *   ['path' => '/post/hello-world', 'code' => 404, 'hits' => 42],
     *   ...
     * ]
     * 
     * @param int $limit
     * @return array<array{path: string, code: int, hits: int}>
     */
    public function getTopErrors(int $limit = 10): array;

    /**
     * Get trending pages
     *
     * @param int $limit
     * @return array
     */
    public function getTrendingPages(int $limit = 10): array;

    /**
     * Get the most common referring domains.
     *
     * Expected format:
     * [
     *   ['domain' => 'example.com', 'hits' => 42],
     *   ...
     * ]
     * 
     * @param int $limit
     * @return array<array{domain: string, hits: int}>
     */
    public function getTopReferrers(int $limit = 10): array;

    /**
     * Get the most common referring domains for a specific page.
     *
     * Expected format:
     * [
     *   ['domain' => 'example.com', 'hits' => 42],
     *   ...
     * ]
     * 
     * @param string $path
     * @param int $limit
     * @return array<array{domain: string, hits: int}>
     */
    public function getTopReferrersForPage(string $path, int $limit = 10): array;

    /**
     * Get page views per day for paths
     *
     * Expected format:
     * [
     *   ['date' => '2026-04-29', 'total' => 123],
     *   ...
     * ]
     *
     * @param string[] $paths
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array<array{date: string, total: int}>
     */
    public function getPageViewsPerDayForPaths(
        array $paths,
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array;

    /**
     * Get page stats over time
     *
     * Expected format:
     * [
     *   ['date' => '2026-04-29', 'views' => 123],
     *   ...
     * ]
     *
     * @param Page $page
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array<array{date: string, views: int}>
     */
    public function getPageStatsOverTime(Page $page, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Get series stats over time
     *
     * Expected format:
     * [
     *   ['date' => '2026-04-29', 'views' => 123],
     *   ...
     * ]
     *
     * @param Series $series
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array<array{date: string, views: int}>
     */
    public function getSeriesStatsOverTime(Series $series, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Get top visitor countries/regions.
     * 
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int $limit
     * @return array
     */
    public function getTopRegions(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 10): array;

    /**
     * Get RSS subscriber stats over time.
     * 
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array
     */
    public function getSubscriberStatsOverTime(\DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Get current subscribers per feed path.
     * 
     * @return array
     */
    public function getCurrentSubscribersPerFeed(): array;

    /**
     * Get top bot user-agents in the given date range.
     *
     * Expected format:
     * [
     *   ['user_agent' => 'Googlebot/2.1', 'total' => 1234],
     *   ...
     * ]
     */
    public function getTopBots(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 15): array;
}