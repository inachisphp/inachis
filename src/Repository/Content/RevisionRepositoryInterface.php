<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Entity\Content\Revision;

/**
 * Interface for revision repositories
 */
interface RevisionRepositoryInterface
{
    /**
     * Get all revisions
     *
     * @param int $offset The offset from which to return results from
     * @param int $limit  The maximum number of results to return
     * @param list{0: string, 1?:array<string, string|list<string>>}|list{} $where
     * @param list<list{0: string, 1: string}>|string|list{} $order
     * @param list<string>|list{} $groupBy
     * @param list<list{0: string, 1: string, 2: string, 3?: string}>|list{} $join
     * @return Paginator<Revision>
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
     * Gets the maximum number of items to show
     * 
     * @return int The maximum number of items to show
     */
    public function getMaxItemsToShow(): int;
}
