<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Analytics;

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
     */
    public function getTopPages(int $limit = 10): array;

    /**
     * Get total views in a date range.
     */
    public function getTotalViews(
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): int;

    /**
     * Get the most common paths that result in a 4xx or 5xx error.
     *
     * Expected format:
     * [
     *   ['path' => '/post/hello-world', 'code' => 404, 'hits' => 42],
     *   ...
     * ]
     */
    public function getTopErrors(int $limit = 10): array;
}