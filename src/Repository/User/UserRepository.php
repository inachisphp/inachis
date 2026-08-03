<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\User;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\User\User;
use Inachis\Model\ContentQueryParameters;
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
     * @param ContentQueryParameters $params
     * @return Paginator<User> The paginator
     */
    public function getFiltered(ContentQueryParameters $params): Paginator
    {
        $filters = $params->getFilters();
        $joins = [];
        $where = [
            'q.isRemoved = \'0\'',
            []
        ];

        if (!empty($filters['keyword']) && is_string($filters['keyword'])) {
            $where[0] .= ' AND (q.displayName LIKE :keyword OR q.username LIKE :keyword OR q.email LIKE :keyword )';
            $where[1]['keyword'] = '%' . $filters['keyword']  . '%';
        }
        if (!empty($filters['status'])) {
            $where[0] .= ' AND q.isActive = :active';
            $where[1]['active'] = $filters['status'] === 'enabled';
        }
        if (!empty($filters['role'])) {
            $joins[] = ['join', 'q.assignedRoles', 'r'];
            $where[0] .= ' AND r.identifier = :role';
            $where[1]['role'] = $filters['role'];
        }
        if (!empty($filters['last sign-in']) && is_numeric($filters['last sign-in'])) {
            $where[0] .= ' AND q.lastLoginAt <= :lastLoginAt';
            $where[1]['lastLoginAt'] = new \DateTime('-' . $filters['last sign-in'] . ' days');
        }
        if (!empty($filters['password modified']) && is_numeric($filters['password modified'])) {
            $where[0] .= ' AND q.passwordChangedAt <= :passwordModified';
            $where[1]['passwordModified'] = new \DateTime('-' . $filters['password modified'] . ' days');
        }
        if (!empty($filters['2fa status'])) {
            $where[0] .= ' AND q.totpEnabled = :totpEnabled';
            $where[1]['totpEnabled'] = $filters['2fa status'];
        }
        
        $sort = match ($params->getSort()) {
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
            $params->getLimit(),
            $params->getOffset(),
            $where,
            $sort,
            [ 'q.id' ],
            $joins,
        );
    }

    /**
     * Returns a count of {@link User}s with Administrator role
     *
     * @return int
     */
    public function countActiveAdministrators(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->join('u.assignedRoles', 'r')
            ->where('u.isRemoved = false')
            ->andWhere('u.isActive = true')
            ->andWhere('LOWER(r.identifier) IN (:roles)')
            ->setParameter('roles', ['admin', 'administrator'])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
