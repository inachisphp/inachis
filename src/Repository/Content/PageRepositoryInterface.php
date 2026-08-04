<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Content;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Entity\Content\Page;

/**
 * Interface for page repositories.
 */
interface PageRepositoryInterface
{
    /**
     * Get all pages.
     *
     * @param int                                                            $limit   The maximum number of results to return
     * @param int                                                            $offset  The offset from which to return results from
     * @param list{0: string, 1?:array<string, string|list<string>>}|list{}  $where
     * @param list<list{0: string, 1: string}>|string|list{}                 $order
     * @param list<string>|list{}                                            $groupBy
     * @param list<list{0: string, 1: string, 2: string, 3?: string}>|list{} $join
     *
     * @return Paginator<Page>
     */
    public function getAll(
        int $limit = 25,
        int $offset = 0,
        array $where = [],
        array|string $order = [],
        array $groupBy = [],
        array $join = [],
    ): Paginator;

    /**
     * Get the maximum number of items to show in the admin interface.
     */
    public function getMaxItemsToShow(): int;

    /**
     * Get all pages of a certain type, ordered by post date.
     *
     * @param array{
     *   categories?:array<string>,
     *   tags?:array<string>,
     *   status?:string,
     *   visible?:bool,
     *   keyword?:string,
     *   excludeIds?:list<string>,
     *   fromDate?:\DateTimeImmutable,
     *   toDate?:\DateTimeImmutable,
     *   expired?:string
     * } $filters
     *
     * @return Paginator<Page>
     */
    public function getFilteredOfTypeByPostDate(
        array $filters,
        string $type,
        int $limit,
        int $offset,
        string $sort,
    ): Paginator;
}
