<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\User;

use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserTrustedDevice;
use Inachis\Repository\AbstractRepository;

/**
 * Repository for {@link UserTrustedDevice} entities
 * 
 * @extends AbstractRepository<UserTrustedDevice>
 */
class UserTrustedDeviceRepository extends AbstractRepository
{
    /**
     * Creates a new instance of the UserTrustedDeviceRepository
     * 
     * @param ManagerRegistry $registry The registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTrustedDevice::class);
    }

    /**
     * Returns all active trusted devices for a user.
     *
     * @param User $user
     * @return list<UserTrustedDevice>
     */
    public function getTrustedDevices(User $user): array
    {
        /** @var list<UserTrustedDevice> */
        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.expiresAt > :now')
            ->setParameter('user', $user->getId(), 'uuid_binary')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('d.lastUsedAt', 'DESC')
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns trusted devices for a user by selector
     *
     * @param User $user
     * @param string $selector
     * @return UserTrustedDevice|null
     */
    public function findBySelector(
        User $user,
        string $selector
    ): ?UserTrustedDevice
    {
        /** @var UserTrustedDevice|null */
        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.selector = :selector')
            ->setParameter('user', $user->getId(), 'uuid_binary')
            ->setParameter('selector', $selector)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Removes all expired trusted devices for the given user.
     *
     * @param User $user
     */
    public function removeExpiredDevices(User $user): void
    {
        $this->createQueryBuilder('d')
            ->delete()
            ->where('d.user = :user')
            ->andWhere('d.expiresAt <= :now')
            ->setParameter('user', $user->getId(), 'uuid_binary')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Removes all trusted devices for a user
     *
     * @param User $user
     */
    public function removeAll(User $user): void
    {
        $this->createQueryBuilder('d')
            ->delete()
            ->where('d.user = :user')
            ->setParameter('user', $user->getId(), 'uuid_binary')
            ->getQuery()
            ->execute();
    }
}
