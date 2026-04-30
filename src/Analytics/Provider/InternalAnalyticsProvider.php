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
use Inachis\Repository\SeriesRepository;
use Inachis\Repository\UrlRepository;

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
        private SeriesRepository $seriesRepository,
        private UrlRepository $urlRepository,
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
     * Get the most common paths that result in a 4xx or 5xx error.
     *
     * @param int $limit
     * @return array
     */
    public function getTopErrors(int $limit = 10): array
    {
        return $this->analyticsRepository->getTopErrors($limit);
    }

    /**
     * Get trending pages
     *
     * @param int $limit
     * @return array
     */
    public function getTrendingPages(int $limit = 10): array
    {
        return array_map(function ($row) {
            $row['title'] = $this->resolveTitle($row['path']);
            return $row;
        }, $this->analyticsRepository->getTrendingPages($limit));
    }

    /**
     * Resolve title
     *
     * @param string $path
     * @return string
     */
    private function resolveTitle(string $path): string
    {
        if ($path === '/' || empty($path)) {
            return 'Home';
        }
        if (preg_match('#^/[\d]{4}/[/\d]{2}/[/\d]{2}/(.+)$#', $path, $matches)) {
            $slug = ltrim($matches[0], '/');
            $url = $this->urlRepository->findOneBy([
                'link' => $slug
            ]);
            $content = $url?->getContent();
            return $content
                ? $content->getTitle() . ($content->getSubTitle() ? ' - ' . $content->getSubTitle() : '')
                : $path;
        }
        if (preg_match('#/([\d]{4})\-(.+)$#', $path, $matches)) {
            $year = $matches[1];
            $title = $matches[2];
            $series = $this->seriesRepository->getPublicSeriesByYearAndUrl(
                $year,
                $title
            );
            return $series
                ? $series->getTitle() . ($series->getSubTitle() ? ' - ' . $series->getSubTitle() : '')
                : $path;
        }

        if (preg_match('#^/tag/(.+)$#', $path, $matches)) {
            $tag = $matches[1];
            return 'Tag: ' . $tag;
        }

        if (preg_match('#^/category/(.+)$#', $path, $matches)) {
            $category = $matches[1];
            return 'Category: ' . $category;
        }

        if (preg_match('#^/author/(.+)$#', $path, $matches)) {
            $author = $matches[1];
            return 'Author: ' . $author;
        }

        return $path;
    }
}