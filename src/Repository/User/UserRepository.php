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
     * @return Paginator<User> The paginator
     */
    public function getFiltered(array $filters, int $limit, int $offset): Paginator
    {
        $where = [
            'q.isRemoved = \'0\'',
            []
        ];
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.displayName LIKE :keyword OR q.username LIKE :keyword OR q.email LIKE :keyword )';
            $where[1]['keyword'] = '%' . $filters['keyword']  . '%';
        }
        return $this->getAll(
            $limit,
            $offset,
            $where,
            [
                [ 'q.displayName', 'ASC' ],
            ]
        );
    }
}
