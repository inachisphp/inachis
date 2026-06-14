<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\{Page, ReviewThread};
use Inachis\Entity\User\User;

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

    /**
     * Returns a count of review threads by resolution status.
     */
    private function countByResolutionStatus(
        bool $resolved,
        ?User $user,
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to
    ): int {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.resolved = :resolved')
            ->setParameter('resolved', $resolved);

        if ($user !== null) {
            $qb->andWhere('t.resolvedBy = :user')
                ->setParameter('user', $user);
        }

        if ($from !== null) {
            $qb->andWhere('t.resolvedAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to !== null) {
            $qb->andWhere('t.resolvedAt <= :to')
                ->setParameter('to', $to);
        }

        return (int) $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns a count of open reviews
     *
     * @param User|null $user
     * @param DateTimeImmutable|null $from
     * @param DateTimeImmutable|null $to
     * @return int
     */
    public function countOpen(?User $user, ?DateTimeImmutable $from, ?DateTimeImmutable $to): int
    {
        return $this->countByResolutionStatus(false, $user, $from, $to);
    }

    /**
     * Returns a count of resolved reviews
     *
     * @param User|null $user
     * @param DateTimeImmutable|null $from
     * @param DateTimeImmutable|null $to
     * @return int
     */
    public function countResolved(?User $user, ?DateTimeImmutable $from, ?DateTimeImmutable $to): int
    {
        return $this->countByResolutionStatus(true, $user, $from, $to);
    }

    /**
     * Returns a count of assigned reviews
     *
     * @return int
     */
    public function countAssignedReviews(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.assignedTo IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }
 
    /**
     * Returns a count of assigned reviews
     *
     * @return int
     */
    public function countUnassignedReviews(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.assignedTo IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the number of reviews assigned to a specific user
     *
     * @param User $user
     * @return int
     */
    public function countAssignedReviewsForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}