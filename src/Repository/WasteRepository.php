<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\User\User;
use Inachis\Entity\Waste\Waste;
use Inachis\Repository\WasteRepositoryInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for {@link Waste} entities
 */
class WasteRepository extends AbstractRepository implements WasteRepositoryInterface
{
    /**
     * Creates a new instance of the WasteRepository
     * 
     * @param ManagerRegistry $registry The registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Waste::class);
    }

    /**
     * Deletes all waste for a user
     * 
     * @param User $user The user
     * @return int The number of waste items deleted
     */
    public function deleteWasteByUser(User $user): int
    {
        $result = $this->createQueryBuilder('w')
            ->delete(Waste::class, 'w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute()
        ;
        if (!is_int($result)) {
            throw new \RuntimeException('Failed to delete waste');
        }
        return $result;
    }

    /**
     * Returns a count of the number of deleted items
     * 
     * @return int The number of waste items
     */
    public function getWasteCount(): int
    {
        return $this->count([]);
    }

    /**
     * Gets filtered waste
     * 
     * @param array<string, mixed> $filters The filters
     * @param int $offset The offset
     * @param int $limit The limit
     * @param string $sort The sort
     * @return Paginator<Waste> The paginator
     */
    public function getFiltered(array $filters, int $offset, int $limit, string $sort): Paginator
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
