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
     * @param int $offset The offset from which to return results from
     * @param int $limit  The maximum number of results to return
     * @param list{0: string, 1?:array<string, string|list<string>>}|list{} $where
     * @param list<list{0: string, 1: string}>|string|list{} $order
     * @param list<string>|list{} $groupBy
     * @param list<list{0: string, 1: string, 2: string, 3?: string}>|list{} $join
     * @return Paginator<T>
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
     * @param int $offset
     * @param int $limit
     * @param string|null $sortBy
     * @return Paginator<T>
     */
    public function getFiltered(array $filters, int $offset, int $limit, ?string $sortBy = 'title asc'): Paginator;
}

