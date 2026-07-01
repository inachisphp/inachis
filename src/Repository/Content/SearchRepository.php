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
    /** @var Connection */
    private Connection $connection;

    /**
     * Constructor for Search Repository
     *
     * @param ManagerRegistry $registry
     * @param Connection $connection
     */
    public function __construct(ManagerRegistry $registry, Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct($registry, SearchResult::class);
    }

    /**
     * Return an orderBy value based on a provided string
     *
     * @param string $orderBy
     * @return string
     */
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
     * Perform search across all available types
     * 
     * @param string $keyword
     * @param int $limit
     * @param int $offset
     * @param string $orderBy
     * @return SearchResult
     */
    public function search(?string $keyword, int $limit = 25, int $offset = 0, string  $orderBy = 'relevance DESC, contentDate DESC'): SearchResult
    {
        return $this->searchWithScope($keyword, $limit, $offset, $orderBy, true);
    }

    /**
     * Perform a front-end search excluding results front-end users would not be interested in, such
     * as {@link Image} resukts
     * 
     * @param string $keyword
     * @param int $limit
     * @param int $offset
     * @param string $orderBy
     * @return SearchResult
     */
    public function searchPublic(?string $keyword, int $limit = 25, int $offset = 0, string $orderBy = 'relevance DESC, contentDate DESC'): SearchResult
    {
        return $this->searchWithScope($keyword, $limit, $offset, $orderBy, false);
    }

    /**
     * Performs the actual search
     * 
     * @param string|null $keyword
     * @param int $limit
     * @param int $offset
     * @param string $orderBy
     * @param bool $includeImages
     * @return SearchResult
     */
    private function searchWithScope(?string $keyword, int $limit, int $offset, string $orderBy, bool $includeImages): SearchResult
    {
        if (empty($keyword)) {
            return new SearchResult([], 0, $limit, $offset);
        }

        $orderBy = $this->determineOrderBy($orderBy);
        $sql = sprintf('%s ORDER BY %s LIMIT :limit OFFSET :offset;',
            $this->getSQLUnion([
                'p.id, p.title as title, p.sub_title, p.content, CONCAT(UCASE(LEFT(type, 1)), LCASE(SUBSTRING(type, 2))) AS type, p.post_date AS contentDate, p.updated_at, p.author_id as author,
                MATCH(p.title, p.sub_title, p.content) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
                's.id, s.title as title, s.sub_title, s.description AS content, \'Series\' AS type, s.last_date AS contentDate, s.updated_at, s.author_id AS author,
                MATCH(s.title, s.sub_title, s.description) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
                'i.id, i.title as title, i.filename as sub_title, i.alt_text as content, \'Image\' as type, updated_at as contentDate, i.updated_at, i.author_id as author,
                MATCH(i.title, i.alt_text, i.description) AGAINST(:kw IN NATURAL LANGUAGE MODE) AS relevance',
            ], $includeImages),
            $orderBy,
        );

        $statement = $this->connection->prepare($sql);
        $statement->bindValue('kw', strtolower((string) $keyword), 'string');
        $statement->bindValue('limit', $limit, 'integer');
        $statement->bindValue('offset', $offset,  'integer');

        /** @var list<array{
         *     id: string,
         *     title: string,
         *     sub_title: string,
         *     content: string,
         *     type: string,
         *     contentDate: string,
         *     updatedAt: string,
         *     author: string,
         *     relevance: float
         * }> $results
         */
        $results = $statement->executeQuery()->fetchAllAssociative();
        // $results = array_map(
        //     static fn(array $row): array => [
        //         'id' => is_string($row['id']) ? $row['id'] : '',
        //         'title' => is_string($row['title']) ? $row['title'] : '',
        //         'sub_title' => is_string($row['sub_title']) ? $row['sub_title'] : '',
        //         'content' => is_string($row['content']) ? $row['content'] : '',
        //         'type' => is_string($row['type']) ? $row['type'] : '',
        //         'contentDate' => is_string($row['contentDate']) ? $row['contentDate'] : '',
        //         'updated_at' => is_string($row['updated_at']) ? $row['updated_at'] : '',
        //         'author' => null,
        //         'relevance' => is_float($row['relevance']) ? $row['relevance'] : 0.00,
        //     ],
        //     $statement->executeQuery()->fetchAllAssociative()
        // );
        $total = $this->getSearchTotalResults($keyword, $includeImages);

        return new SearchResult($results, (int) $total, $limit, $offset);
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
