<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\NavigationTab;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for navigation tabs
 */
class NavigationTabRepository extends AbstractRepository
{
    /**
     * Constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NavigationTab::class);
    }

    /**
     * Returns the maximum position
     * 
     * @return int
     */
    public function getMaxPosition(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COALESCE(MAX(t.position), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns paginated tabs ordered by position
     * 
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @return Paginator<NavigationTab>
     */
    public function getFiltered(array $filters, int $offset, int $limit): Paginator
    {
        return $this->getAll(
            $offset,
            $limit,
            [],
            [
                [ 'q.position', 'ASC' ],
            ]
        );
    }

    /**
     * Returns tabs ordered by position
     * 
     * @return array<NavigationTab>
     */
    public function getAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all tabs indexed by UUID string for easy lookup
     * 
     * @return array<string, NavigationTab>
     */
    public function findAllIndexedById(): array
    {
        $tabs = $this->getAllOrdered();

        $result = [];
        foreach ($tabs as $tab) {
            $result[$tab->getId()->toString()] = $tab;
        }

        return $result;
    }

    /**
     * Returns active tabs ordered by position
     * 
     * @return array<NavigationTab>
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns active tabs ordered by position
     * 
     * @return array<string, array{url: string}>
     */
    public function findActiveOrderedUrlsIndexedByLabel(): array
    {
        $tabs = $this->createQueryBuilder('t')
            ->where('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($tabs as $tab) {
            $result[$tab->getTitle()] = [ 'url' => $tab->getUrl(), ];
        }

        return $result;
    }
    
}
