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
     * @param list<array<mixed>> $where
     * @param list<array<mixed>>|string $order
     * @param list<array<mixed>>|string $groupBy
     * @param list<array<mixed>> $join
     * @return Paginator<Series>
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
     * @return int
     */
    public function getMaxItemsToShow(): int;
}
