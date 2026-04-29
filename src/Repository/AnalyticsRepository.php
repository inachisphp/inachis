<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

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
	 * @return array
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
	 * @param DateTimeInterface $from
	 * @param DateTimeInterface $to
	 * @return array
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
	 * @param DateTimeInterface $from
	 * @param DateTimeInterface $to
	 * @return int
	 */
	public function getTotalViews(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->db->fetchOne(
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
        return (int) $this->db->fetchOne(
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
    }
}
