<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\User;
use Inachis\Entity\Waste;
use Inachis\Repository\WasteRepositoryInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Waste|null find($id, $lockMode = null, $lockVersion = null)
 * @method Waste|null findOneBy(array $criteria, array $orderBy = null)
 * @method Waste[]    findAll()
 * @method Waste[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WasteRepository extends AbstractRepository implements WasteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Waste::class);
    }
    /**
     * @param User $user
     * @return int
     */
    public function deleteWasteByUser(User $user): int
    {
        return $this->createQueryBuilder('w')
            ->delete(Waste::class, 'w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute()
        ;
    }

        /**
     * @param $filters
     * @param $offset
     * @param $limit
     * @return Paginator
     */
    public function getFiltered($filters, $offset, $limit, $sort): Paginator
    {
        $where = [];
        if (!empty($filters['keyword'])) {
            $where = [
                '(q.title LIKE :keyword OR q.subTitle LIKE :keyword OR q.description LIKE :keyword )',
                [
                    'keyword' => '%' . $filters['keyword']  . '%',
                ],
            ];
        }
        $sort = match ($sort) {
            'title desc' => [
                ['q.title', 'DESC'],
                ['q.subTitle', 'DESC'],
            ],
            'modDate asc' => [['q.modDate', 'ASC']],
            'modDate desc' => [['q.modDate', 'DESC']],
            'lastDate asc' => [['q.lastDate', 'ASC']],
            'lastDate desc' => [
                ['CASE WHEN q.lastDate IS NULL THEN 1 ELSE 0 END', 'DESC'],
                ['q.lastDate', 'DESC']
            ],
            default => [
                ['q.title', 'ASC'],
                ['q.subTitle', 'ASC'],
            ],
        };
        return $this->getAll(
            $offset,
            $limit,
            $where,
            $sort
        );
    }
}
