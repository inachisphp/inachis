<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Inachis\Model\SearchResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SearchResult>
 */
class SearchRepository extends ServiceEntityRepository
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
     * @param string $keyword
     * @param int $offset
     * @param int $limit
     * @param string $orderBy
     * @return SearchResult<int, array<string, mixed>>
     */
    public function search(?string $keyword, int $offset = 0, int $limit = 25, string  $orderBy = 'relevance DESC, contentDate DESC'): SearchResult
    {
        return $this->searchWithScope($keyword, $offset, $limit, $orderBy, true);
    }

    /**
     * @param string $keyword
     * @param int $offset
     * @param int $limit
     * @param string $orderBy
     * @return SearchResult<int, array<string, mixed>>
     */
    public function searchPublic(?string $keyword, int $offset = 0, int $limit = 25, string $orderBy = 'relevance DESC, contentDate DESC'): SearchResult
    {
        return $this->searchWithScope($keyword, $offset, $limit, $orderBy, false);
    }

    /**
     * @param string $keyword
     * @param int $offset
     * @param int $limit
     * @param string $orderBy
     * @param bool $includeImages
     * @return SearchResult<int, array<string, mixed>>
     */
    private function searchWithScope(?string $keyword, int $offset, int $limit, string $orderBy, bool $includeImages): SearchResult
    {
        if (empty($keyword)) {
            return new SearchResult([], 0, $offset, $limit);
        }

        $orderBy = $this->determineOrderBy($orderBy);
        $sql = sprintf('%s ORDER BY %s LIMIT :limit OFFSET :offset;',
            $this->getSQLUnion([
                'p.id, p.title as title, p.sub_title, p.content, CONCAT(UCASE(LEFT(type, 1)), LCASE(SUBSTRING(type, 2))) AS type, p.post_date AS contentDate, p.mod_date, p.author_id as author,
                MATCH(p.title, p.sub_title, p.content) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
                's.id, s.title as title, s.sub_title, s.description AS content, \'Series\' AS type, s.last_date AS contentDate, s.mod_date, s.author_id AS author,
                MATCH(s.title, s.sub_title, s.description) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
                'i.id, i.title as title, i.filename as sub_title, i.alt_text as content, \'Image\' as type, mod_date as contentDate, i.mod_date, i.author_id as author,
                MATCH(i.title, i.alt_text, i.description) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
            ], $includeImages),
            $orderBy,
        );

        $statement = $this->connection->prepare($sql);
        $statement->bindValue('kw', strtolower((string) $keyword), 'string');
        $statement->bindValue('limit', $limit, 'integer');
        $statement->bindValue('offset', $offset,  'integer');

        $results = $statement->executeQuery()->fetchAllAssociative();
        $total = $this->getSearchTotalResults($keyword, $includeImages);

        return new SearchResult($results, (int) $total, $offset, $limit);
    }

    /**
     * Returns the total search results
     *
     * @param string|null $keyword
     * @param bool $includeImages
     * @return int
     * @throws Exception
     */
    private function getSearchTotalResults(?string $keyword, bool $includeImages = true): int
    {
        if (empty($keyword)) {
            return 0;
        }
        $sql = sprintf('SELECT COUNT(*) AS total FROM (%s) AS all_results;',
            $this->getSQLUnion([ 'id', 'id', 'id' ], $includeImages)
        );
        $statement = $this->connection->prepare($sql);
        $statement->bindValue('kw', strtolower((string) $keyword), 'string');

        $total = $statement->executeQuery()->fetchOne();
        if (!is_scalar($total)) {
            return 0;
        }

        $totalValue = (string) $total;
        if (!preg_match('/^\d+$/', $totalValue)) {
            return 0;
        }

        return (int) $totalValue;
    }

    /**
     * @param array<int, string> $fieldLists
     */
    protected function getSQLUnion(array $fieldLists, bool $includeImages = true): string
    {
        $sql = sprintf('
            (SELECT %s FROM page p WHERE %s)
            UNION ALL
            (SELECT %s FROM series s WHERE %s)',
            $fieldLists[0],
            $this->getWhereConditions('page'),
            $fieldLists[1],
            $this->getWhereConditions('series')
        );

        if ($includeImages) {
            $sql .= sprintf('
            UNION ALL
            (SELECT %s FROM image i WHERE %s)',
                $fieldLists[2],
                $this->getWhereConditions('image')
            );
        }

        return $sql;
    }

    /**
     * Returns the where conditions for the search based on the search type
     *
     * @param string $type
     * @return string
     */
    protected function getWhereConditions(string $type): string
    {
        return match($type) {
            'image' => 'MATCH(i.title, i.alt_text, i.description) AGAINST(:kw IN NATURAL LANGUAGE MODE)',
            'page' => 'MATCH(p.title, p.sub_title, p.content) AGAINST(:kw IN NATURAL LANGUAGE MODE)',
            'series' => 'MATCH(s.title, s.sub_title, s.description) AGAINST(:kw IN NATURAL LANGUAGE MODE)',
            default => '1=0',
        };
    }
}
