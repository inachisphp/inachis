<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

interface PageRepositoryInterface
{
    /**
     * @return int
     */
    public function getMaxItemsToShow(): int;

    /**
     * @param $filters
     * @param string $type
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @return Paginator
     */
    public function getFilteredOfTypeByPostDate(
        $filters,
        string $type,
        int $offset,
        int $limit,
        string $sort
    ): Paginator;
}
