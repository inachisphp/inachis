<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Media;

use Inachis\Entity\Media\AbstractFile;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Interface for resource repositories
 * 
 * @template T of AbstractFile
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
     * @param int $limit  The maximum number of results to return
     * @param int $offset The offset from which to return results from
     * @param list{0: string, 1?:array<string, string|list<string>>}|list{} $where
     * @param list<list{0: string, 1: string}>|string|list{} $order
     * @param list<string>|list{} $groupBy
     * @param list<list{0: string, 1: string, 2: string, 3?: string}>|list{} $join
     * @return Paginator<T>
     */
    public function getAll(
        int $limit = 25,
        int $offset = 0,
        array $where = [],
        array|string $order = [],
        array $groupBy = [],
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
     * @param array{keyword?: string} $filters
     * @param int $limit
     * @param int $offset
     * @param string|null $sortBy
     * @return Paginator<T>
     */
    public function getFiltered(array $filters, int $limit, int $offset, ?string $sortBy = 'title asc'): Paginator;
}

