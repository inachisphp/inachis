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
     * Returns a filtered list of resource files
     * 
     * @param array{keyword?: string} $filters
     * @param int $offset
     * @param int $limit
     * @param string|null $sortBy
     * @return Paginator<AbstractFile>
     */
    public function getFiltered(array $filters, int $offset, int $limit, ?string $sortBy = 'title asc'): Paginator
    {
        $where = [];
        if (!empty($filters['keyword'])) {
            $where = [
                '(q.altText LIKE :keyword OR q.title LIKE :keyword OR q.description LIKE :keyword )',
                [
                    'keyword' => '%' . $filters['keyword']  . '%',
                ],
            ];
        }
        return $this->getAll(
            $offset,
            $limit,
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
     * @return array<int,string>
     */
    protected function determineOrderBy(?string $orderBy): array
    {
        return match ($orderBy) {
            'title desc' => ['q.title', 'DESC'],
            'createDate asc' => ['q.createDate', 'ASC'],
            'createDate desc' => ['q.createDate', 'DESC'],
            'filesize asc' => ['q.filesize', 'ASC'],
            'filesize desc' => ['q.filesize', 'DESC'],
            'modDate asc' => ['q.modDate', 'ASC'],
            'modDate desc' => ['q.modDate', 'DESC'],
            default => ['q.title', 'ASC'],
        };
    }
}