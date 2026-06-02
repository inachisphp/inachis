<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Interface for revision repositories
 */
interface RevisionRepositoryInterface
{
    /**
     * Get all revisions
     *
     * @param int $offset
     * @param int $limit
     * @param array<int, array<int, string>> $where
     * @param array<int, array<int, string>>|string $order
     * @param array<int, array<int, string>>|string $groupBy
     * @param array<int, array<int, string>> $join
     * @return Paginator<Revision>
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
     * Gets the maximum number of items to show
     * 
     * @return int The maximum number of items to show
     */
    public function getMaxItemsToShow(): int;
}
