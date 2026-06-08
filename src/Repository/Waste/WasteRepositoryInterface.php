<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Waste;

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
     * @param int $offset The offset from which to return results from
     * @param int $limit  The maximum number of results to return
     * @param list<int, array<int, string>> $where
     * @param list<int, array<int, string>>|string $order
     * @param array<string> $groupBy
     * @param list<int, array<int, string>> $join
     * @return Paginator<Waste>
     */
    public function getAll(
        int $offset = 0,
        int $limit = 25,
        array $where = [],
        array|string $order = [],
        array $groupBy = [],
        array $join = []
    ): Paginator;

    /**
     * Get the maximum number of items to show
     *
     * @return int
     */
    public function getMaxItemsToShow(): int;
}
