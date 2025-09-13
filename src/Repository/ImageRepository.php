<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ImageRepository extends AbstractRepository
{
    public const MAX_ITEMS_TO_SHOW_ADMIN = 25;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * @param $filters
     * @param $offset
     * @param $limit
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getFiltered($filters, $offset, $limit)
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
                [ 'q.title', 'ASC' ],
                [ 'q.createDate', 'ASC' ]
            ]
        );
    }
}
