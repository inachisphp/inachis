<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Media;

use Inachis\Entity\Media\AbstractFile;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @template T of AbstractFile
 */
trait DefaultResourceRepository
{
    /**
     * Get the disk usage of all images
     *
     * @return int The disk usage in bytes
     */
    public function getDiskUsage(): int
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('SUM(r.filesize)');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Removes the record of the specified file from the database, the associated file
     * is not removed
     *
     * @param AbstractFile $file
     */
    public function remove(AbstractFile $file): void
    {
        $this->getEntityManager()->remove($file);
        $this->getEntityManager()->flush();
    }

    /**
     * Get all resources
     *
     * @param int $limit  The maximum number of results to return
     * @param int $offset The offset from which to return results from
     * @param list{0: string, 1?:array<string, string|list<string>>}|list{} $where
     * @param list<list{0: string, 1: string}>|string|list{} $order
     * @param list<string>|list{} $groupBy
     * @param list<list{0: string, 1: string, 2: string, 3?: string}>|list{} $join
     * @return Paginator<T>
     */
    abstract public function getAll(
        int $limit = 25,
        int $offset = 0,
        array $where = [],
        array|string $order = [],
        array $groupBy = [],
        array $join = []
    ): Paginator;

    /**
     * Returns a filtered list of resource files
     *
     * @param array{keyword?: string} $filters
     * @param int $limit
     * @param int $offset
     * @param string|null $sortBy
     * @return Paginator<T>
     */
    public function getFiltered(array $filters, int $limit, int $offset, ?string $sortBy = 'title asc'): Paginator
    {
        $where = [
            '1=1',
            [],
        ];
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.altText LIKE :keyword OR q.title LIKE :keyword OR q.description LIKE :keyword )';
            $where[1]['keyword'] = '%' . $filters['keyword']  . '%';
        }
        if (!empty($filters['usage']) && $filters['usage'] === 'notinuse') {
            $ids = $this->getUnusedResourceIds();
            if (!empty($ids)) {
                $where[0] .= ' AND q.id IN (:unusedIds)';
                $where[1]['unusedIds'] = [ 'value' => $ids ];
            }
        }

        return $this->getAll(
            $limit,
            $offset,
            $where,
            [
                $this->determineOrderBy($sortBy),
            ]
        );
    }

    /**
     * Returns an SQL orderBy for the given string
     *
     * @param string|null $orderBy
     * @return list{0: string, 1: string}
     */
    protected function determineOrderBy(?string $orderBy): array
    {
        return match ($orderBy) {
            'title desc' => ['q.title', 'DESC'],
            'createdAt asc' => ['q.createdAt', 'ASC'],
            'createdAt desc' => ['q.createdAt', 'DESC'],
            'filesize asc' => ['q.filesize', 'ASC'],
            'filesize desc' => ['q.filesize', 'DESC'],
            'updatedAt asc' => ['q.updatedAt', 'ASC'],
            'updatedAt desc' => ['q.updatedAt', 'DESC'],
            default => ['q.title', 'ASC'],
        };
    }

    /**
     * Get a list of the IDs for Resources not used in {@link Page} or
     * {@link Series} objects
     *
     * @return list<string>
     */
    protected function getUnusedResourceIds(): array
    {
        return [];
    }
}