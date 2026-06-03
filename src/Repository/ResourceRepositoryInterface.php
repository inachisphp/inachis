<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\Media\AbstractFile;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Interface for resource repositories
 */
interface ResourceRepositoryInterface
{
    /**
     * Get the disk usage of resources
     * 
     * @return int The disk usage in bytes
     */
    public function getDiskUsage(): int;

    /**
     * Get all resources
     * 
     * @param int $offset
     * @param int $limit
     * @param array<int, array<int, string>> $where
     * @param array<int, array<int, string>>|string $order
     * @param array<int, array<int, string>>|string $groupBy
     * @param array<int, array<int, string>> $join
     * @return Paginator<AbstractFile>
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
     * Remove the given resource from the database
     * 
     * @param AbstractFile $download The resource to be removed.
     * @return void
     */
    public function remove(AbstractFile $download): void;

    /**
     * Get all resources with the given filters
     * 
     * @param array<array<string>> $filters
     * @param int $offset
     * @param int $limit
     * @param string|null $sortBy
     * @return Paginator<AbstractFile>
     */
    public function getFiltered($filters, $offset, $limit, ?string $sortBy = 'title asc'): Paginator;
}

