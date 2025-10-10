<?php

namespace App\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

interface PageRepositoryInterface {
    public function getMaxItemsToShow(): int;
    public function getFilteredOfTypeByPostDate($filters, string $type, int $offset, int $limit, string $sort): Paginator;
}