<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

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
     * @param int $offset The offset from which to return results from
     * @param int $limit  The maximum number of results to return
     * @param list{0: string, 1?:array<string, string|list<string>>}|list{} $where
     * @param list<list{0: string, 1: string}>|string|list{} $order
     * @param list<string>|list{} $groupBy
     * @param list<list{0: string, 1: string, 2: string, 3?: string}>|list{} $join
     * @return Paginator<Page>
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
     * Get the maximum number of items to show in the admin interface
     *
     * @return int
     */
    public function getMaxItemsToShow(): int;

    /**
     * Get all pages of a certain type, ordered by post date
     * 
     * @param array{
     *   categories?:array<string>,
     *   tags?:array<string>,
     *   status?:string,
     *   visibility?:string,
     *   keyword?:string,
     *   excludeIds?:string,
     *   fromDate?:string,
     *   toDate?:string
     * } $filters
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
