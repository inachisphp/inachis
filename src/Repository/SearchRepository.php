<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use App\Model\SearchResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

class SearchRepository extends AbstractRepository
{
    private Connection $connection;

    public function __construct(ManagerRegistry $registry, Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct($registry, SearchResult::class);
    }

    /**
     * @throws Exception
     */
    public function search(?string $keyword,
                           int     $offset = 0,
                           int     $limit = 25,
                           string  $orderBy = 'relevance DESC, contentDate DESC'
    ): SearchResult {
        $sql = "
            (
                SELECT
                    s.id, s.title, s.sub_title, s.description AS content, 'Series' AS type, s.last_date AS contentDate, s.mod_date,
                        (
                            (CASE WHEN LOWER(s.title) LIKE :kw THEN 10 ELSE 0 END) +
                            (CASE WHEN LOWER(s.sub_title) LIKE :kw THEN 5 ELSE 0 END) +
                            (CASE WHEN LOWER(s.description) LIKE :kw THEN 2 ELSE 0 END)
                        ) AS relevance
                FROM series s
                WHERE LOWER(s.title) LIKE :kw OR LOWER(s.sub_title) LIKE :kw OR LOWER(s.description) LIKE :kw
            )
            UNION ALL
            (
                SELECT
                    p.id, p.title, p.sub_title, p.content, CONCAT(UCASE(LEFT(type, 1)), LCASE(SUBSTRING(type, 2))) AS type, p.post_date AS contentDate, p.mod_date,
                    (
                        (CASE WHEN LOWER(p.title) LIKE :kw THEN 10 ELSE 0 END) +
                        (CASE WHEN LOWER(p.sub_title) LIKE :kw THEN 5 ELSE 0 END) +
                        (CASE WHEN LOWER(p.content) LIKE :kw THEN 2 ELSE 0 END)
                    ) AS relevance
                FROM page p
                WHERE LOWER(p.title) LIKE :kw OR LOWER(p.sub_title) LIKE :kw OR LOWER(p.content) LIKE :kw
            )
            ORDER BY $orderBy 
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('kw', '%' . strtolower($keyword) . '%');
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);

        $results = $stmt->executeQuery()->fetchAllAssociative();
        $total = $this->getSearchTotalResults($keyword);

        return new SearchResult($results, (int) $total, $offset, $limit);
    }

    /**
     * @throws Exception
     */
    private function getSearchTotalResults($keyword): int
    {
        $sql = "
            SELECT COUNT(*) AS total FROM (
                SELECT id FROM series
                WHERE
                    LOWER(title) LIKE :kw OR LOWER(sub_title) LIKE :kw OR LOWER(description) LIKE :kw
                UNION ALL
                SELECT id FROM page
                WHERE
                    LOWER(title) LIKE :kw OR LOWER(sub_title) LIKE :kw OR LOWER(content) LIKE :kw
            ) AS all_results
        ";
        return $this->connection->prepare($sql)
            ->executeQuery([ 'kw' => '%' . $keyword . '%' ])
            ->fetchOne();
    }
}
