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
        $sql = sprintf('%s ORDER BY %s LIMIT :limit OFFSET :offset;',
            $this->getSQLUnion([
                'p.id, p.title, p.sub_title, p.content, CONCAT(UCASE(LEFT(type, 1)), LCASE(SUBSTRING(type, 2))) AS type, p.post_date AS contentDate, p.mod_date, p.author_id as author,
                MATCH(p.title, p.sub_title, p.content) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
                's.id, s.title, s.sub_title, s.description AS content, \'Series\' AS type, s.last_date AS contentDate, s.mod_date, \'\' AS author, 
                MATCH(s.title, s.sub_title, s.description) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
            ]),
            $orderBy,
        );

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('kw', '%' . strtolower($keyword) . '%');
        $stmt->bindValue('plainKw', strtolower($keyword));
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
        $sql = sprintf('SELECT COUNT(*) AS total FROM (%s) AS all_results;',
            $this->getSQLUnion([ 'id', 'id' ])
        );
        return $this->connection->prepare($sql)
            ->executeQuery([ 'kw' => '%' . $keyword . '%' ])
            ->fetchOne();
    }

    private function getSQLUnion($fieldLists)
    {
        return sprintf('
            (SELECT %s FROM page p WHERE %s)
            UNION ALL
            (SELECT %s FROM series s WHERE %s)',
            $fieldLists[0],
            $this->getWhereConditions('page'),
            $fieldLists[1],
            $this->getWhereConditions('series'),
        );
    }

    private function getWhereConditions($type)
    {
        return match($type) {
            'page' => 'LOWER(p.title) LIKE :kw OR LOWER(p.sub_title) LIKE :kw OR LOWER(p.content) LIKE :kw',
            'series' => 'LOWER(s.title) LIKE :kw OR LOWER(s.sub_title) LIKE :kw OR LOWER(s.description) LIKE :kw',
        };
    }
}
