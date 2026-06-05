<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Analytics;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Inachis\Entity\Content\{Page, Series};

/**
 * Analytics repository
 *
 * This repository is used to store and retrieve analytics data.
 */
class AnalyticsRepository
{
    public function __construct(private Connection $db) {}

	/**
	 * Increment page views
	 *
	 * @param string $path The path of the page
	 * @param string $date The date
	 * @param int $views The number of views
	 */
    public function increment(string $path, string $date, int $views): void
    {
		// $this->db->executeStatement(
		// 	'
		// 	INSERT INTO analytics_page_view (path, date, views)
		// 	VALUES (:path, :date, :views)
		// 	ON DUPLICATE KEY UPDATE views = views + :views
		// 	',
		// 	[
		// 		'path' => $path,
		// 		'date' => $date,
		// 		'views' => $views,
		// 	]
		// );
    }

	/**
	 * Get top pages
	 *
	 * @param int $limit
	 * @return list<array<string, mixed>>
	 */
	public function getTopPages(int $limit = 10): array
	{
		return $this->db->executeQuery(
			'
			SELECT path, SUM(views) as total
			FROM analytics_page_view
			GROUP BY path
			ORDER BY total DESC
			LIMIT ' . (int) $limit
		)->fetchAllAssociative();
	}

	/**
	 * Get page views per day
	 *
	 * @param \DateTimeInterface $from
	 * @param \DateTimeInterface $to
	 * @return array<array<string, mixed>>
	 */
	public function getPageViewsPerDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->db->fetchAllAssociative(
            '
            SELECT date, SUM(views) as total
            FROM analytics_page_view
            WHERE date BETWEEN :from AND :to
            GROUP BY date
            ORDER BY date ASC
            ',
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]
        );
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
        /** @var int $result */
        $result = $this->db->fetchOne(
            '
            SELECT SUM(views)
            FROM analytics_page_view
            WHERE date BETWEEN :from AND :to
            ',
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]
        );

        return (int) $result;
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
        /** @var int $result */
        $result = $this->db->fetchOne(
            '
            SELECT COUNT(DISTINCT visitor_hash)
            FROM analytics_unique_visitor
            WHERE date BETWEEN :from AND :to
            ',
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]
        );

        return (int) $result;
    }

	/**
     * Get the most common paths that result in a 4xx or 5xx error.
     *
     * @param int $limit
     * @return array<array<string, mixed>>
     */
    public function getTopErrors(int $limit = 10): array
    {
        return $this->db->fetchAllAssociative('
            SELECT path, code, SUM(hits) AS hits
            FROM analytics_errors
            GROUP BY path
            ORDER BY hits DESC
            LIMIT ' . (int) $limit
        );
    }

    /**
     * Get trending pages
     *
     * @param int $limit
     * @return list<array{path: mixed, current: int, previous: int, change: float|int|null}>
     */
    public function getTrendingPages(int $limit = 10): array
    {
        $now = new \DateTimeImmutable();

        $thisWeekStart = $now->modify('monday this week')->format('Y-m-d');
        $lastWeekStart = $now->modify('monday last week')->format('Y-m-d');
        $lastWeekEnd   = $now->modify('sunday last week')->format('Y-m-d');

        /** @var list<array{path:string,total:int|string|null}> $current This week */
        $current = $this->db->fetchAllAssociative(
            '
            SELECT path, SUM(views) AS total
            FROM analytics_page_view
            WHERE date >= :start
            GROUP BY path
            ',
            ['start' => $thisWeekStart]
        );

        /** @var list<array{path:string,total:int|string|null}> $previous Last Week */
        $previous = $this->db->fetchAllAssociative(
            '
            SELECT path, SUM(views) AS total
            FROM analytics_page_view
            WHERE date BETWEEN :start AND :end
            GROUP BY path
            ',
            [
                'start' => $lastWeekStart,
                'end'   => $lastWeekEnd,
            ]
        );

        // Index previous
        $prevMap = [];
        foreach ($previous as $row) {
            $prevMap[$row['path']] = (int) $row['total'];
        }

        // Build result
        $results = [];

        foreach ($current as $row) {
            $path = $row['path'];
            $currentViews = (int) $row['total'];
            $prevViews = $prevMap[$path] ?? 0;

            $change = $prevViews > 0
                ? (($currentViews - $prevViews) / $prevViews) * 100
                : null;

            $results[] = [
                'path' => $path,
                'current' => $currentViews,
                'previous' => $prevViews,
                'change' => $change,
            ];
        }

        // Sort by current views
        usort($results, fn ($a, $b) => $b['current'] <=> $a['current']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Get the most common referring domains.
     *
     * @param int $limit
     * @return list<array<string,mixed>>
     */
    public function getTopReferrers(int $limit = 10): array
    {
        return $this->db->fetchAllAssociative(
            '
            SELECT domain, SUM(hits) AS total
            FROM analytics_referrer
            GROUP BY domain
            ORDER BY total DESC
            LIMIT ' . (int) $limit
        );
    }

    /**
     * Get the most common referring domains for a specific page.
     *
     * @param string $path
     * @param int $limit
     * @return list<array<string,mixed>>
     */
    public function getTopReferrersForPage(string $path, int $limit = 10): array
    {
        return $this->db->fetchAllAssociative(
            '
            SELECT domain, SUM(hits) AS total
            FROM analytics_referrer
            WHERE path = :path
            GROUP BY domain
            ORDER BY total DESC
            LIMIT ' . (int) $limit,
            ['path' => $path]
        );
    }

    /**
     * Get page views per day for paths
     *
     * @param string[] $paths
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array<array<string,mixed>>
     */
    public function getPageViewsPerDayForPaths(
        array $paths,
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array {
        if (empty($paths)) {
            return [];
        }

        /** @var list<array{date:string,total:int|string|null}> $data */
        $data = $this->db->executeQuery(
            '
            SELECT date, SUM(views) as total
            FROM analytics_page_view
            WHERE path IN (:paths)
            AND date BETWEEN :from AND :to
            GROUP BY date
            ORDER BY date ASC
            ',
            [
                'paths' => $paths,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            [
                'paths' => ArrayParameterType::STRING,
            ]
        )->fetchAllAssociative();

        return $this->fillMissingDates($data, $from, $to);
    }

    /**
     * Get page views per day for a page
     *
     * @param Page $page
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array<array<string,mixed>>
     */
	public function getPageStatsOverTime(Page $page, \DateTimeInterface $from, \DateTimeInterface $to): array
	{
		$paths = $page->getUrls()->map(fn($url) => '/' . $url->getLink());

		return $this->getPageViewsPerDayForPaths($paths->toArray(), $from, $to);
	}

    /**
     * Get page views per day for a series
     *
     * @param Series $series
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array<array<string,mixed>>
     */
    public function getSeriesStatsOverTime(Series $series, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        if (!empty($series->getLastDate()) && !empty($series->getUrl())) {
            $paths = ['/' . $series->getLastDate()->format('Y') . '-' . $series->getUrl()];
        } else {
            $paths = [];
        }

        return $this->getPageViewsPerDayForPaths($paths, $from, $to);
    }

    /**
     * Fill in missing dates
     *
     * @param list<array{date:string,total:int|string|null}> $data
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array<array<string,mixed>>
     */
    public function fillMissingDates(array $data, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $indexed = [];
        foreach ($data as $row) {
            $indexed[$row['date']] = (int) $row['total'];
        }

        $result = [];
        $current = new \DateTimeImmutable($from->format('Y-m-d'));
        $end = new \DateTimeImmutable($to->format('Y-m-d'));

        while ($current <= $end) {
            $key = $current->format('Y-m-d');

            $result[] = [
                'date' => $key,
                'views' => $indexed[$key] ?? 0,
            ];

            $current = $current->modify('+1 day');
        }

        return $result;
    }

    /**
     * Get top visitor countries/regions.
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int $limit
     * @return array<array<string,mixed>>
     */
    public function getTopRegions(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 10): array
    {
        return $this->db->fetchAllAssociative(
            '
            SELECT country_code, country_name, SUM(hits) AS total
            FROM analytics_regions
            WHERE date BETWEEN :from AND :to
            GROUP BY country_code, country_name
            ORDER BY total DESC
            LIMIT ' . (int) $limit,
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]
        );
    }

    /**
     * Get RSS subscriber stats over time.
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array<array<string,mixed>>
     */
    public function getSubscriberStatsOverTime(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        /** @var list<array{date:string,total:int|string|null}> $data */
        $data = $this->db->fetchAllAssociative(
            '
            SELECT date, SUM(subscribers) AS total
            FROM analytics_subscribers
            WHERE date BETWEEN :from AND :to
            GROUP BY date
            ORDER BY date ASC
            ',
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]
        );

        // Fill missing dates
        $indexed = [];
        foreach ($data as $row) {
            $indexed[$row['date']] = (int) $row['total'];
        }

        $result = [];
        $current = new \DateTimeImmutable($from->format('Y-m-d'));
        $end = new \DateTimeImmutable($to->format('Y-m-d'));

        while ($current <= $end) {
            $key = $current->format('Y-m-d');

            $result[] = [
                'date' => $key,
                'subscribers' => $indexed[$key] ?? 0,
            ];

            $current = $current->modify('+1 day');
        }

        return $result;
    }

    /**
     * Get current subscribers per feed path.
     *
     * @return array<array<string,mixed>>
     */
    public function getCurrentSubscribersPerFeed(): array
    {
        return $this->db->fetchAllAssociative(
            '
            SELECT s.path, s.subscribers
            FROM analytics_subscribers s
            INNER JOIN (
                SELECT path, MAX(date) AS max_date
                FROM analytics_subscribers
                GROUP BY path
            ) latest ON s.path = latest.path AND s.date = latest.max_date
            ORDER BY s.subscribers DESC
            '
        );
    }
}
