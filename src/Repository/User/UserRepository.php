<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\User;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\User\User;
use Inachis\Repository\AbstractRepository;

/**
 * Repository for {@link User} entities
 * 
 * @extends AbstractRepository<User>
 */
class UserRepository extends AbstractRepository
{
    /**
     * Creates a new instance of the UserRepository
     * 
     * @param ManagerRegistry $registry The registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Finds a user by username
     * 
     * @param string $username The username to search for
     * @return User|null The user if found
     */
    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    /**
     * Gets filtered users
     * 
     * @param array{keyword?: string} $filters The filters
     * @param int $limit The limit
     * @param int $offset The offset
     * @param string $sort The sort order
     * @return Paginator<User> The paginator
     */
    public function getFiltered(array $filters, int $limit, int $offset, string $sort = ''): Paginator
    {
        $where = [
            'q.isRemoved = \'0\'',
            []
        ];
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.displayName LIKE :keyword OR q.username LIKE :keyword OR q.email LIKE :keyword )';
            $where[1]['keyword'] = '%' . $filters['keyword']  . '%';
        }
        if (!empty($filters['status'])) {
            $where[0] .= ' AND q.isActive = :active';
            $where[1]['active'] = $filters['status'] === 'enabled';
        }
        if (!empty($filters['last sign-in'])) {
            $where[0] .= ' AND q.lastLoginAt <= :lastLoginAt';
            $where[1]['lastLoginAt'] = new \DateTime('-' . $filters['last sign-in'] . ' days');
        }
        if (!empty($filters['password modified'])) {
            $where[0] .= ' AND q.passwordChangedAt <= :passwordModified';
            $where[1]['passwordModified'] = new \DateTime('-' . $filters['password modified'] . ' days');
        }
        if (!empty($filters['2fa status'])) {
            $where[0] .= ' AND q.totpEnabled = :totpEnabled';
            $where[1]['totpEnabled'] = $filters['2fa status'];
        }
        $sort = match ($sort) {
            'createdAt asc' => [['q.createdAt', 'ASC']],
            'createdAt desc' => [['q.createdAt', 'DESC']],
            'updatedAt asc' => [['q.updatedAt', 'ASC']],
            'updatedAt desc' => [['q.updatedAt', 'DESC']],
            'username asc' => [['q.username', 'ASC']],
            'username desc' => [['q.username', 'DESC']],
            'displayName desc' => [['q.displayName', 'DESC']],
            default => [['q.displayName', 'ASC']],
        };
        return $this->getAll(
            $limit,
            $offset,
            $where,
            $sort
        );
    }
}
