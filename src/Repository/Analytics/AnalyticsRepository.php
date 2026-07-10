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
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
	 * @param int $limit
	 * @return list<array{path: string, total: numeric-string}>
	 */
	public function getTopPages(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 10
    ): array {
        /** @var list<array{path: string, total: numeric-string}> */
		return $this->db->executeQuery(
			'
			SELECT path, SUM(views) as total
			FROM analytics_page_view
            WHERE date BETWEEN :from AND :to
			GROUP BY path
			ORDER BY total DESC
			LIMIT ' . $limit,
            [
                'from' => $from->format('Y-m-d'),
                'to'   => $to->format('Y-m-d'),
            ]
		)->fetchAllAssociative();
	}

	/**
	 * Get page views per day
	 *
	 * @param \DateTimeInterface $from
	 * @param \DateTimeInterface $to
	 * @return list<array{date: string, total: numeric-string}>
	 */
	public function getPageViewsPerDay(
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array {
        /** @var list<array{date: string, total: numeric-string}> */
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
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int $limit
     * @return list<array{path: string, code: string, hits: numeric-string}>
     */
    public function getTopErrors(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 10
    ): array {
        /** @var list<array{path: string, code: string, hits: numeric-string}> */
        return $this->db->fetchAllAssociative('
            SELECT path, code, SUM(hits) AS hits
            FROM analytics_errors
            WHERE date BETWEEN :from AND :to
            GROUP BY path, code
            ORDER BY hits DESC
            LIMIT ' . (int) $limit,
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]
        );
    }

    /**
     * Get trending pages by comparing two date ranges.
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param \DateTimeInterface $previousFrom
     * @param \DateTimeInterface $previousTo
     * @param int $limit
     * @return list<array{path: string, current: int, previous: int, change: float|int|null}>
     */
    public function getTrendingPages(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        \DateTimeInterface $previousFrom,
        \DateTimeInterface $previousTo,
        int $limit = 10
    ): array {
        /** @var list<array{path: string, total: numeric-string}> $current */
        $current = $this->db->fetchAllAssociative(
            '
            SELECT path, SUM(views) AS total
            FROM analytics_page_view
            WHERE date BETWEEN :from AND :to
            GROUP BY path
            ',
            [
                'from' => $from->format('Y-m-d'),
                'to'   => $to->format('Y-m-d'),
            ]
        );

        /** @var list<array{path: string, total: numeric-string}> $previous */
        $previous = $this->db->fetchAllAssociative(
            '
            SELECT path, SUM(views) AS total
            FROM analytics_page_view
            WHERE date BETWEEN :from AND :to
            GROUP BY path
            ',
            [
                'from' => $previousFrom->format('Y-m-d'),
                'to'   => $previousTo->format('Y-m-d'),
            ]
        );

        $previousMap = [];
        foreach ($previous as $row) {
            $previousMap[$row['path']] = (int) $row['total'];
        }

        $results = [];

        foreach ($current as $row) {
            $path = $row['path'];
            $currentViews = (int) $row['total'];
            $previousViews = $previousMap[$path] ?? 0;

            $change = $previousViews > 0
                ? (($currentViews - $previousViews) / $previousViews) * 100
                : null;

            $results[] = [
                'path' => $path,
                'current' => $currentViews,
                'previous' => $previousViews,
                'change' => $change,
            ];
        }

        usort(
            $results,
            static fn(array $a, array $b): int => $b['current'] <=> $a['current']
        );

        return array_slice($results, 0, $limit);
    }

    /**
     * Get the most common referring domains.
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int $limit
     * @return list<array{domain: string, total: numeric-string}>
     */
    public function getTopReferrers(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 10
    ): array {
        /** @var list<array{domain: string, total: numeric-string}> */
        return $this->db->fetchAllAssociative(
            '
            SELECT domain, SUM(hits) AS total
            FROM analytics_referrer
            WHERE date BETWEEN :from AND :to
            GROUP BY domain
            ORDER BY total DESC
            LIMIT ' . (int) $limit,
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]
        );
    }

    /**
     * Get the most common referring domains for a specific page.
     *
     * @param string $path
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int $limit
     * @return list<array{domain: string, total: numeric-string}>
     */
    public function getTopReferrersForPage(
        string $path,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 10
    ): array {
        /** @var list<array{domain: string, total: numeric-string}> */
        return $this->db->fetchAllAssociative(
            '
            SELECT domain, SUM(hits) AS total
            FROM analytics_referrer
            WHERE path = :path
            AND date BETWEEN :from AND :to
            GROUP BY domain
            ORDER BY total DESC
            LIMIT ' . (int) $limit,
            [
                'path' => $path,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]
        );
    }

    /**
     * Get page views per day for paths
     *
     * @param string[] $paths
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return list<array{date: string, views: int}>
     */
    public function getPageViewsPerDayForPaths(
        array $paths,
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array {
        if (empty($paths)) {
            return [];
        }

        /** @var list<array{date:string, total:numeric-string}> $data */
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

        return $this->fillMissingSeries($data, $from, $to, 'views');
    }

    /**
     * Get page views per day for a page
     *
     * @param Page $page
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return list<array{date: string, views: int}>
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
     * @return list<array{date: string, views: int}>
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
     * Get top visitor countries/regions.
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int $limit
     * @return list<array{country_code: string, country_name: string, total: numeric-string}>
     */
    public function getTopRegions(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 10): array
    {
        /** @var list<array{country_code: string, country_name: string, total: numeric-string}> */
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
     * @return list<array{date: string, subscribers: int}>
     */
    public function getSubscriberStatsOverTime(
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array {
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

        /** @var list<array{date:string,subscribers:int}> */
        return $this->fillMissingSeries(
            $data,
            $from,
            $to,
            'subscribers'
        );
    }

    /**
     * Get current subscribers per feed path.
     *
     * @return list<array{path: string, subscribers: numeric-string}>
     */
    public function getCurrentSubscribersPerFeed(): array
    {
        /** @var list<array{path: string, subscribers: numeric-string}> */
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
            LIMIT 10
            '
        );
    }

    /**
     * Get top bot user-agents ordered by total hits in the given date range.
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int $limit
     * @return list<array{user_agent: string, total: numeric-string}>
     */
    public function getTopBots(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 15): array
    {
        /** @var list<array{user_agent: string, total: numeric-string}> */
        return $this->db->fetchAllAssociative(
            '
            SELECT user_agent, SUM(hits) AS total
            FROM analytics_bots
            WHERE date BETWEEN :from AND :to
            GROUP BY user_agent
            ORDER BY total DESC
            LIMIT ' . (int) $limit,
            [
                'from' => $from->format('Y-m-d'),
                'to'   => $to->format('Y-m-d'),
            ]
        );
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
        $today = new \DateTimeImmutable('today');
        $yesterday = $today->modify('-1 day');

        $thisMonth = $today->modify('first day of this month');
        $lastMonth = $today->modify('first day of last month');

        /** @var array{
         *     views_today:string|int|null,
         *     views_yesterday:string|int|null,
         *     views_this_month:string|int|null,
         *     views_last_month:string|int|null
         * } $views
         */
        $views = $this->db->fetchAssociative(
            '
            SELECT
                COALESCE(SUM(CASE
                    WHEN date = :today
                    THEN views
                    ELSE 0
                END), 0) AS views_today,

                COALESCE(SUM(CASE
                    WHEN date = :yesterday
                    THEN views
                    ELSE 0
                END), 0) AS views_yesterday,

                COALESCE(SUM(CASE
                    WHEN date >= :thisMonth
                    THEN views
                    ELSE 0
                END), 0) AS views_this_month,

                COALESCE(SUM(CASE
                    WHEN date >= :lastMonth
                    AND date < :thisMonth
                    THEN views
                    ELSE 0
                END), 0) AS views_last_month

            FROM analytics_page_view
            ',
            [
                'today' => $today->format('Y-m-d'),
                'yesterday' => $yesterday->format('Y-m-d'),
                'thisMonth' => $thisMonth->format('Y-m-d'),
                'lastMonth' => $lastMonth->format('Y-m-d'),
            ]
        );

        /** @var array{
         *     unique_this_month:string|int|null,
         *     unique_last_month:string|int|null
         * } $visitors
         */
        $visitors = $this->db->fetchAssociative(
            '
            SELECT
                COUNT(DISTINCT CASE
                    WHEN date >= :thisMonth
                    THEN visitor_hash
                    ELSE NULL
                END) AS unique_this_month,

                COUNT(DISTINCT CASE
                    WHEN date >= :lastMonth
                    AND date < :thisMonth
                    THEN visitor_hash
                    ELSE NULL
                END) AS unique_last_month

            FROM analytics_unique_visitor
            ',
            [
                'thisMonth' => $thisMonth->format('Y-m-d'),
                'lastMonth' => $lastMonth->format('Y-m-d'),
            ]
        );

        return [
            'viewsToday' => (int) ($views['views_today'] ?? 0),
            'viewsYesterday' => (int) ($views['views_yesterday'] ?? 0),
            'viewsThisMonth' => (int) ($views['views_this_month'] ?? 0),
            'viewsLastMonth' => (int) ($views['views_last_month'] ?? 0),

            'uniqueVisitorsThisMonth' => (int) ($visitors['unique_this_month'] ?? 0),
            'uniqueVisitorsLastMonth' => (int) ($visitors['unique_last_month'] ?? 0),
        ];
    }

    /**
     * Fill missing dates in a time series.
     *
     * The query should return rows with a "date" column and a "total" column.
     *
     * @param list<array{date:string,total:int|string|null}> $data
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param string $valueKey The key to use in the returned array (e.g. "views", "subscribers")
     * @return list<array{date:string}>
     */
    private function fillMissingSeries(
        array $data,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        string $valueKey
    ): array {
        $indexed = [];

        foreach ($data as $row) {
            $indexed[$row['date']] = (int) $row['total'];
        }

        $result = [];

        $current = new \DateTimeImmutable($from->format('Y-m-d'));
        $end = new \DateTimeImmutable($to->format('Y-m-d'));

        while ($current <= $end) {
            $date = $current->format('Y-m-d');

            $result[] = [
                'date' => $date,
                $valueKey => $indexed[$date] ?? 0,
            ];

            $current = $current->modify('+1 day');
        }

        return $result;
    }
}
