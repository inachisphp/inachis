<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\Content\Page;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Interface for page repositories
 */
interface PageRepositoryInterface
{
    /**
     * Get all pages
     * 
     * @param int $offset
     * @param int $limit
     * @param array<int, array<int, string>> $where
     * @param array<int, array<int, string>>|string $order
     * @param array<int, array<int, string>>|string $groupBy
     * @param array<int, array<int, string>> $join
     * @return Paginator<Page>
     */
    public function getAll(
        int $offset = 0,
        int $limit = 25,
        array $where = [],
        array|string $order = [],
        array|string $groupBy = [],
        array $join = []
    ): Paginator;

    /**
     * Get the maximum number of items to show in the admin interface
     
     * @return int
     */
    public function getMaxItemsToShow(): int;

    /**
     * Get all pages of a certain type, ordered by post date
     * 
     * @param array<int, array<int, string>> $filters
     * @param string $type
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @return Paginator<Page>
     */
    public function getFilteredOfTypeByPostDate(
        array $filters,
        string $type,
        int $offset,
        int $limit,
        string $sort
    ): Paginator;
}
