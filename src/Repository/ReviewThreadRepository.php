<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\{Page, ReviewThread};

class ReviewThreadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewThread::class);
    }

    /**
     * @return array<ReviewThread>
     */
    public function findOpenForPage(Page $page): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.page = :page')
            ->andWhere('t.resolved = false')
            ->setParameter('page', $page)
            ->orderBy('t.updated', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<ReviewThread>
     */
    public function findAllForPage(Page $page): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.page = :page')
            ->setParameter('page', $page)
            ->orderBy('t.updated', 'DESC')
            ->getQuery()
            ->getResult();
    }
}