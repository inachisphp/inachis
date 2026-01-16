<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\AbstractFile;
use Doctrine\ORM\Tools\Pagination\Paginator;

trait DefaultResourceRepository
{
    public const MAX_ITEMS_TO_SHOW_ADMIN = 25;

    /**
     * @param AbstractFile $file
     */
    public function remove(AbstractFile $file): void
    {
        $this->getEntityManager()->remove($file);
        $this->getEntityManager()->flush();
    }

    /**
     * @param $filters
     * @param $offset
     * @param $limit
     * @param string|null $sortBy
     * @return Paginator
     */
    public function getFiltered($filters, $offset, $limit, ?string $sortBy = 'title asc'): Paginator
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
    * @param string $orderBy
    * @return array[]
    */
    protected function determineOrderBy(string $orderBy): array
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