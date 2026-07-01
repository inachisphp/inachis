<?php

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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
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
