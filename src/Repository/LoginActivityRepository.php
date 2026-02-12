<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use DateTimeImmutable;
use Inachis\Entity\{LoginActivity, User};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for LoginActivity
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
     * @param int $limit
     * @return array
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.loggedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.loggedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $user
     * @param string $fingerprint
     * @return bool
     */
    public function deviceExists(User $user, string $fingerprint): bool
    {
        // @todo Change this so it gets distinct fingerprints instead
        // $all = $this->createQueryBuilder('l')
        //     ->select('1')
        //     ->where('l.user = :user')
        //     ->andWhere('l.type = :type')
        //     ->setParameter('user', $user)
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
     * @param int $minutes
     * @param int $threshold
     * @return array
     */
    public function recentFailures(int $minutes = 15, int $threshold = 5): array
    {
        return $this->createQueryBuilder('l')
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
    }

    /**
     * @param int $limit
     * @return array
     */
    public function newDeviceLogins(int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->where('JSON_EXTRACT(l.extraData, "$.newDevice") = true')
            ->orderBy('l.loggedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $type
     * @param DateTimeImmutable $cutoff
     * @return int
     */
    public function countOlderThan(string $type, DateTimeImmutable $cutoff): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(LoginActivity::class, 'l')
            ->where('l.type = :type')
            ->andWhere('l.loggedAt < :cutoff')
            ->setParameter('type', $type)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
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
            $ids = $this->getEntityManager()->createQueryBuilder()
                ->select('l.id')
                ->from(LoginActivity::class, 'l')
                ->where('l.type = :type')
                ->andWhere('l.loggedAt < :cutoff')
                ->setParameter('type', $type)
                ->setParameter('cutoff', $cutoff)
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getScalarResult();
            if (!$ids) {
                break;
            }

            $idArray = array_map(fn($row) => $row['id'], $ids);

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
}
