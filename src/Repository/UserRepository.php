<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends AbstractRepository //ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param $filters
     * @param $offset
     * @param $limit
     * @return Paginator
     */
    public function getFiltered($filters, $offset, $limit): Paginator
    {
        $where = [
            'q.isRemoved = \'0\'',
            []
        ];
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.displayName LIKE :keyword OR q.username LIKE :keyword OR q.email LIKE :keyword )';
            $where[1]['keyword'] = '%' . $filters['keyword']  . '%';
        }
        return $this->getAll(
            $offset,
            $limit,
            $where,
            [
                [ 'q.displayName', 'ASC' ],
            ]
        );
    }
}
