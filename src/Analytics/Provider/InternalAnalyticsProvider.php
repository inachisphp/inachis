<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Analytics\Provider;

use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Repository\AnalyticsRepository;
use Inachis\Repository\PageRepository;

/**
 * Internal analytics provider
 *
 * This provider is used to track analytics data in the internal database
 */
class InternalAnalyticsProvider implements AnalyticsProviderInterface
{
    public function __construct(
        private AnalyticsRepository $analyticsRepository,
        private PageRepository $pageRepository,
    ) {}

    /**
     * Get top pages
     *
     * @param int $limit
     * @return array
     */
    public function getTopPages(int $limit = 10): array
    {
        $rows = $this->analyticsRepository->getTopPages($limit);
        return array_map(function ($row) {
            $row['title'] = $this->resolveTitle($row['path']);
            return $row;
        }, $rows);
    }

    /**
     * Get page views per day
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array
     */
    public function getPageViewsPerDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->analyticsRepository->getPageViewsPerDay($from, $to);
    }

    /**
     * Get total views
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return int
     */
    public function getTotalViews(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->analyticsRepository->getTotalViews($from, $to);
    }

    /**
     * Get monthly unique visitors
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return int
     */
    public function getMonthlyUniqueVisitors(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->analyticsRepository->getMonthlyUniqueVisitors($from, $to);
    }

    /**
     * Resolve title
     *
     * @param string $path
     * @return string
     */
    private function resolveTitle(string $path): string
    {
        if (preg_match('#^/post/(.+)$#', $path, $matches)) {
            $slug = $matches[1];
            $page = $this->pageRepository->findOneByLink($slug);
            return $page?->getTitle() ?? $path;
        }

        return $path;
    }
}