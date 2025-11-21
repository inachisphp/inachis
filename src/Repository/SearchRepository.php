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

    protected function determineOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'contentDate asc' => 'contentDate ASC',
            'contentDate desc' => 'contentDate DESC',
            'relevance asc' => 'relevance ASC, contentDate DESC',
            'title desc' => 'title DESC',
            'title asc' => 'title ASC',
            'type desc' => 'type DESC',
            'type asc' => 'type ASC',
            default => 'relevance DESC, contentDate DESC',
        };
    }

    /**
     * @throws Exception
     */
    public function search(?string $keyword, int $offset = 0, int $limit = 25, string  $orderBy = 'relevance DESC, contentDate DESC'): SearchResult
    {
        $orderBy = $this->determineOrderBy($orderBy);
        $sql = sprintf('%s ORDER BY %s LIMIT :limit OFFSET :offset;',
            $this->getSQLUnion([
                'p.id, p.title as title, p.sub_title, p.content, CONCAT(UCASE(LEFT(type, 1)), LCASE(SUBSTRING(type, 2))) AS type, p.post_date AS contentDate, p.mod_date, p.author_id as author,
                MATCH(p.title, p.sub_title, p.content) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
                's.id, s.title as title, s.sub_title, s.description AS content, \'Series\' AS type, s.last_date AS contentDate, s.mod_date, s.author_id AS author, 
                MATCH(s.title, s.sub_title, s.description) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
                'i.id, i.title as title, i.filename as sub_title, i.alt_text as content, \'Image\' as type, mod_date as contentDate, i.mod_date, i.author_id as author,
                MATCH(i.title, i.alt_text, i.description) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
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
            $this->getSQLUnion([ 'id', 'id', 'id' ])
        );
        return $this->connection->prepare($sql)
            ->executeQuery([ 'kw' => '%' . $keyword . '%' ])
            ->fetchOne();
    }

    protected function getSQLUnion($fieldLists)
    {
        return sprintf('
            (SELECT %s FROM page p WHERE %s)
            UNION ALL
            (SELECT %s FROM series s WHERE %s)
            UNION ALL
            (SELECT %s FROM image i WHERE %s)',
            $fieldLists[0],
            $this->getWhereConditions('page'),
            $fieldLists[1],
            $this->getWhereConditions('series'),
            $fieldLists[2],
            $this->getWhereConditions('image'),
        );
    }

    protected function getWhereConditions($type)
    {
        return match($type) {
            'image' => 'MATCH(i.title, i.alt_text, i.description) AGAINST(:kw IN NATURAL LANGUAGE MODE)',
            'page' => 'MATCH(p.title, p.sub_title, p.content) AGAINST(:kw IN NATURAL LANGUAGE MODE)',
            'series' => 'MATCH(s.title, s.sub_title, s.description) AGAINST(:kw IN NATURAL LANGUAGE MODE)',
        };
    }
}
