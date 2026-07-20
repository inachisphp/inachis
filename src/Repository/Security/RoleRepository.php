<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Security;

use Inachis\Entity\Security\Role;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Role>
 */
class RoleRepository extends AbstractRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * Gets an associative array of role names as 'identifier' => 'name'
     *
     * @param integer $limit
     * @return array<string, string>
     */
    public function getRoleNames($limit = 25)
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.identifier, r.name')
            ->orderBy('r.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'name', 'identifier');
    }

    /**
     * Returns a {@link Role} by the provided identifier
     *
     * @param string $identifier
     * @return Role
     */
    public function getRoleByIdentifier(string $identifier)
    {
        /** @var Role */
        return $this->createQueryBuilder('r')
            ->select('r.identifier, r.name')
            ->where('identifier = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Gets filtered users
     * 
     * @param array{keyword?: string} $filters The filters
     * @param int $limit The limit
     * @param int $offset The offset
     * @return Paginator<Role> The paginator
     */
    public function getFiltered(array $filters, int $limit, int $offset): Paginator
    {
        $where = [
            '1=1',
            $filters,
        ];
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.name LIKE :keyword)';
            $where[1]['keyword'] = '%' . $filters['keyword']  . '%';
        }
        return $this->getAll(
            $limit,
            $offset,
            $where,
            [
                [ 'q.name', 'ASC' ],
            ]
        );
    }
}
