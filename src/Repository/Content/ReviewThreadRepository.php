<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\{Page, ReviewThread};

/**
 * Repository for handling {@link ReviewThread} entities
 * 
 * @extends ServiceEntityRepository<ReviewThread>
 */
class ReviewThreadRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewThread::class);
    }

    /**
     * Returns an array of open {@link ReviewThread} objects for the give {@link Page}
     * 
     * @return array<ReviewThread>
     */
    public function findOpenForPage(Page $page): array
    {
        /** @var array<ReviewThread> */
        return $this->createQueryBuilder('t')
            ->where('t.page = :page')
            ->andWhere('t.resolved = false')
            ->setParameter('page', $page)
            ->orderBy('t.updated', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all {@link ReviewThread} for a given {@link Page} including both open and
     * closed.
     * 
     * @return array<ReviewThread>
     */
    public function findAllForPage(Page $page): array
    {
        /** @var array<ReviewThread> */
        return $this->createQueryBuilder('t')
            ->where('t.page = :page')
            ->setParameter('page', $page)
            ->orderBy('t.updated', 'DESC')
            ->getQuery()
            ->getResult();
    }
}