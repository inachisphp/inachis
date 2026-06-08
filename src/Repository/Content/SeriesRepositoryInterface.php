<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Entity\Content\Series;

interface SeriesRepositoryInterface
{
    /**
     * Returns a Paginator of all series
     *
     * @param int $offset The offset from which to return results from
     * @param int $limit  The maximum number of results to return
     * @param list<int, array<int, string>> $where
     * @param list<int, array<int, string>>|string $order
     * @param array<string> $groupBy
     * @param list<int, array<int, string>> $join
     * @return Paginator<Series>
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
     * @return int
     */
    public function getMaxItemsToShow(): int;
}
