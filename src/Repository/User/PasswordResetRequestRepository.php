<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\User;

use Inachis\Entity\User\{PasswordResetRequest, User};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use DateInterval;
use DateTimeImmutable;

/**
 * Repository for password reset requests
 * 
 * @extends ServiceEntityRepository<PasswordResetRequest>
 */
class PasswordResetRequestRepository extends ServiceEntityRepository
{
    /**
     * PasswordResetRequestRepository constructor
     * 
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetRequest::class);
    }

    /**
     * Finds all active password reset requests for a given user.
     * 
     * @param User $user
     * @return list<PasswordResetRequest>
     */
    public function findActiveByUser(User $user): array
    {
        /** @var list<PasswordResetRequest> */
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.used = false')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds the latest active password reset request for a given user.
     * 
     * @param User $user
     * @return PasswordResetRequest|null
     * @throws NonUniqueResultException
     */
    public function findLatestActiveForUser(User $user): ?PasswordResetRequest
    {
        /** @var PasswordResetRequest|null */
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.used = false')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds the latest active password reset request for a given hash.
     * 
     * @param string $hash
     * @return PasswordResetRequest|null
     * @throws NonUniqueResultException
     */
    public function findLatestActiveByHash(string $hash): ?PasswordResetRequest
    {
        /** @var PasswordResetRequest|null */
        return $this->createQueryBuilder('r')
            ->andWhere('r.hash = :hash')
            ->andWhere('r.used = false')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('hash', $hash)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Purges expired password reset requests.
     * 
     * @return int
     */
    public function purgeExpiredHashes(): int
    {
        /** @var int */
        return $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.expiresAt < :now')
            ->setParameter('now', (new DateTimeImmutable())->sub(new DateInterval('PT1H')))
            ->getQuery()
            ->getResult();
    }
}
