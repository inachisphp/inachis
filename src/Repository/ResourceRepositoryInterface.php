<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use App\Entity\AbstractFile;
use Doctrine\ORM\Tools\Pagination\Paginator;

interface ResourceRepositoryInterface
{
    public function getAll(
        int $offset = 0,
        int $limit = 25,
        array $where = [],
        array|string $order = [],
        array|string $groupBy = [],
        array $join = []
    ): Paginator;

    /**
     * @param AbstractFile $download
     * @return void
     */
    public function remove(AbstractFile $download): void;

    /**
     * @param $filters
     * @param $offset
     * @param $limit
     * @param string|null $sortBy
     * @return Paginator
     */
    public function getFiltered($filters, $offset, $limit, ?string $sortBy = 'title asc'): Paginator;
}

