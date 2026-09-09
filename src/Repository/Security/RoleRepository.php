<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Repository\Security;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Security\Role;
use Inachis\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<Role>
 */
class RoleRepository extends AbstractRepository
{
    /**
     * Constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * Gets an associative array of role names as 'identifier' => 'name'.
     *
     * @return array<string, string>
     */
    public function getRoleNames(int $limit = 25): array
    {
        /** @var array<int, array{identifier: string, name: string}> $rows */
        $rows = $this->createQueryBuilder('r')
            ->select('r.identifier, r.name')
            ->orderBy('r.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        /** @var array<string, string> $result */
        $result = array_column($rows, 'name', 'identifier');

        return $result;
    }

    /**
     * Returns a {@link Role} by the provided identifier.
     */
    public function getRoleByIdentifier(string $identifier): ?Role
    {
        /** @var Role|null $result */
        $result = $this->createQueryBuilder('r')
            ->where('r.identifier = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

    /**
     * Gets filtered users.
     *
     * @param array{keyword?: string} $filters The filters
     * @param int                     $limit   The limit
     * @param int                     $offset  The offset
     *
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
            $where[1]['keyword'] = '%'.$filters['keyword'].'%';
        }

        return $this->getAll(
            $limit,
            $offset,
            $where,
            [
                ['q.name', 'ASC'],
            ],
        );
    }
}
