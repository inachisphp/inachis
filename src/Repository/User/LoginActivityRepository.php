<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\User;

use DateTimeImmutable;
use Inachis\Entity\User\{LoginActivity, User};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Model\ContentQueryParameters;

/**
 * Repository for LoginActivity
 *
 * @extends ServiceEntityRepository<LoginActivity>
 */
class LoginActivityRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginActivity::class);
    }

    /**
     * Returns the most recent login activity, defaults to 50
     *
     * @param int $limit
     * @return Paginator<LoginActivity>
     */
    public function findRecent(int $limit = 50): Paginator
    {
        $query = $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.loggedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query, false);
    }

    /**
     * Returns the most recent login activity, defaults to 50
     *
     * @param ContentQueryParameters $params
     * @return Paginator<LoginActivity>
     */
    public function getFiltered(ContentQueryParameters $params): Paginator
    {
        $query = $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u');
        $filters = $params->getFilters();
        if (!empty($filters['keyword']) && is_string($filters['keyword'])) {
            $query
                ->where('u.username LIKE :username')
                ->setParameter('username', $filters['keyword']);
        }
        [$field, $direction] = $this->determineOrderBy($params->getSort());
        $query
            ->orderBy($field, $direction)
            ->setMaxResults($params->getLimit())
            ->getQuery();

        return new Paginator($query, false);
    }

    /**
     * Returns most recent login activity for the specified {@link User}, defaults to
     * showing 50 records.
     *
     * @param User $user
     * @param int $limit
     * @return Paginator<LoginActivity>
     */
    public function findByUser(User $user, int $limit = 50): Paginator
    {
        $query = $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->where('l.user = :user')
            ->setParameter('user', $user->getId(), 'uuid_binary')
            ->orderBy('l.loggedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query, false);
    }

    /**
     * Check whether the device has been seen before
     *
     * @param User $user
     * @param string $fingerprint
     * @return bool
     */
    public function deviceExists(User $user, string $fingerprint): bool
    {
        // TODO: Change this so it gets distinct fingerprints instead
        // $all = $this->createQueryBuilder('l')
        //     ->select('1')
        //     ->where('l.user = :user')
        //     ->andWhere('l.type = :type')
            // ->setParameter('user', $user->getId(), 'uuid_binary')
        //     ->setParameter('type', 'success')
        //     ->setMaxResults(50)
        //     ->getQuery()
        //     ->getOneOrNullResult();

        //     foreach ($all as $login) {
        //         $extraData = $login->getExtraData() ?? [];
        //         if (($extraData['fingerprint'] ?? null) === $fingerprint) {
                    return true; // device is known
            //     }
            // }

            // return true;
    }

    /**
     * Returns a list of recent failed login attempts in the last 15 minutes (default) to a threshold
     * of 5 (default).
     *
     * @param int $minutes
     * @param int $threshold
     * @return array<int,array{ipAddress: string, attempts:int}>
     */
    public function recentFailures(int $minutes = 15, int $threshold = 5): array
    {
        /** @var array<int,array{ipAddress: string, attempts:int}> $result */
       $result = $this->createQueryBuilder('l')
            ->select('l.ipAddress, COUNT(l.id) as attempts')
            ->where('l.type = :failure')
            ->andWhere('l.loggedAt > :since')
            ->setParameter('failure', 'failure')
            ->setParameter('since', new DateTimeImmutable("-{$minutes} minutes"))
            ->groupBy('l.ipAddress')
            ->having('COUNT(l.id) >= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
        return $result;
    }

    /**
     * Returns a list of logins where it was from a new device
     *
     * @param int $limit
     * @return array<int,LoginActivity>
     */
    public function newDeviceLogins(int $limit = 20): array
    {
        /** @var array<int,LoginActivity> $result */
        $result = $this->createQueryBuilder('l')
            ->where('JSON_EXTRACT(l.extraData, "$.newDevice") = true')
            ->orderBy('l.loggedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        return $result;
    }

    /**
     * Returns a count of login records of a specific type, older than a specified
     * DateTime
     *
     * @param string $type
     * @param DateTimeImmutable $cutoff
     * @return int
     */
    public function countOlderThan(string $type, DateTimeImmutable $cutoff): int
    {
        /** @var int $result */
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(LoginActivity::class, 'l')
            ->where('l.type = :type')
            ->andWhere('l.loggedAt < :cutoff')
            ->setParameter('type', $type)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getSingleScalarResult();
        return (int) $result;
    }

    /**
     * Deletes records of a specific type, older than a specified date, in a batch size
     * that defaults to 1000 maximum, witha. callback function provided.
     *
     * @param string $type
     * @param DateTimeImmutable $cutoff
     * @param int $batchSize
     * @param callable|null $progressCallback
     * @return int
     */
    public function deleteOlderThan(
        string $type,
        DateTimeImmutable $cutoff,
        int $batchSize = 1000,
        ?callable $progressCallback = null
    ): int {
        $totalDeleted = 0;

        do {
            /** @var array<int,array{id: string}> $ids */
            $ids = $this->getEntityManager()->createQueryBuilder()
                ->select('l.id')
                ->from(LoginActivity::class, 'l')
                ->where('l.type = :type')
                ->andWhere('l.loggedAt < :cutoff')
                ->setParameter('type', $type)
                ->setParameter('cutoff', $cutoff)
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult();
            if (!$ids) {
                break;
            }

            $idArray = array_map(fn($row) => $row['id'], $ids);

            /** @var int $deleted */
            $deleted = $this->getEntityManager()->createQueryBuilder()
                ->delete(LoginActivity::class, 'l')
                ->where('l.id IN (:ids)')
                ->setParameter('ids', $idArray)
                ->getQuery()
                ->execute();
            $totalDeleted += $deleted;

            if ($progressCallback) {
                $progressCallback($deleted, $totalDeleted);
            }

            $this->getEntityManager()->clear();
        } while (count($ids) === $batchSize);

        return $totalDeleted;
    }

    /**
     * Determine the order by clause for the query builder
     *
     * @param string $orderBy
     * @return list{0: string, 1: string}
     */
    protected function determineOrderBy(string $orderBy): array
    {
        return match ($orderBy) {
            default => ['l.loggedAt', 'DESC'],
        };
    }
}
