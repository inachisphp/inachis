<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\Waste\Waste;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Interface for waste repositories
 */
interface WasteRepositoryInterface
{
    /**
     * Get all waste items
     *
     * @param int $offset
     * @param int $limit
     * @param array<int, array<int, string>> $where
     * @param array<int, array<int, string>>|string $order
     * @param array<int, array<int, string>>|string $groupBy
     * @param array<int, array<int, string>> $join
     * @return Paginator<Waste>
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
     * Get the maximum number of items to show
     *
     * @return int
     */
    public function getMaxItemsToShow(): int;
}
