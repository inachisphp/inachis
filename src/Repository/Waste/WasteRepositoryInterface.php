<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
     * @param int $limit  The maximum number of results to return
     * @param int $offset The offset from which to return results from
     * @param list{0: string, 1?:array<string, string|list<string>>}|list{} $where
     * @param list<list{0: string, 1: string}>|string|list{} $order
     * @param list<string>|list{} $groupBy
     * @param list<list{0: string, 1: string, 2: string, 3?: string}>|list{} $join
     * @return Paginator<Waste>
     */
    public function getAll(
        int $limit = 25,
        int $offset = 0,
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
