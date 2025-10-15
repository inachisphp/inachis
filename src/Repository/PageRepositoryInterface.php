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
    public function getMaxItemsToShow(): int;
    public function getFilteredOfTypeByPostDate($filters, string $type, int $offset, int $limit, string $sort): Paginator;
}
