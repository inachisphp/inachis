<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ImageRepository extends AbstractRepository
{
    public const MAX_ITEMS_TO_SHOW_ADMIN = 25;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * @param Series $series
     */
    public function remove(Image $image): void
    {
        $this->getEntityManager()->remove($image);
        $this->getEntityManager()->flush();
    }

    /**
     * @param $filters
     * @param $offset
     * @param $limit
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getFiltered($filters, $offset, $limit, ?string $sortby = 'title asc'): Paginator
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
        switch ($sortby) {
            case 'title desc':
                $sortby = ['q.title', 'DESC'];
                break;
            case 'createDate asc':
                $sortby = ['q.createDate', 'ASC'];
                break;
            case 'createDate desc':
                $sortby = ['q.createDate', 'DESC'];
                break;
            case 'modDate asc':
                $sortby = ['q.modDate', 'ASC'];
                break;
            case 'modDate desc':
                $sortby = ['q.modDate', 'DESC'];
                break;
            case 'title asc':
            default:
                $sortby = ['q.title', 'ASC'];
        }
        return $this->getAll(
            $offset,
            $limit,
            $where,
            [
                $sortby,
            ]
        );
    }
}
