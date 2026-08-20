<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Content;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\ReviewThread;
use Inachis\Entity\User\User;
use Inachis\Enum\ReviewStatus;

/**
 * Repository for handling {@link ReviewThread} entities.
 *
 * @extends ServiceEntityRepository<ReviewThread>
 */
class ReviewThreadRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewThread::class);
    }

    /**
     * Returns an array of open {@link ReviewThread} objects for the give {@link Page}.
     *
     * @return array<ReviewThread>
     */
    public function findOpenForPage(Page $page): array
    {
        /* @var array<ReviewThread> */
        return $this->createQueryBuilder('t')
            ->where('t.page = :page')
            ->andWhere('t.status = :status')
            ->setParameter('page', $page)
            ->setParameter('status', ReviewStatus::OPEN)
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
        /* @var array<ReviewThread> */
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
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): int {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.resolved = :resolved')
            ->setParameter('resolved', $resolved);

        if (null !== $user) {
            $qb->andWhere('t.resolvedBy = :user')
                ->setParameter('user', $user);
        }

        if (null !== $from) {
            $qb->andWhere('t.resolvedAt >= :from')
                ->setParameter('from', $from);
        }

        if (null !== $to) {
            $qb->andWhere('t.resolvedAt <= :to')
                ->setParameter('to', $to);
        }

        return (int) $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns a count of open reviews.
     */
    public function countOpen(?User $user, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): int
    {
        return $this->countByResolutionStatus(false, $user, $from, $to);
    }

    /**
     * Returns a count of resolved reviews.
     */
    public function countResolved(?User $user, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): int
    {
        return $this->countByResolutionStatus(true, $user, $from, $to);
    }

    /**
     * Returns a count of assigned reviews.
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
     * Returns a count of assigned reviews.
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
     * Returns the number of reviews assigned to a specific user.
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
