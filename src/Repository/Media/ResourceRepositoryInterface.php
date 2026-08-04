<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Media;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Entity\Media\AbstractFile;

/**
 * Interface for resource repositories.
 *
 * @template T of AbstractFile
 */
interface ResourceRepositoryInterface
{
    /**
     * Get the disk usage of resources.
     *
     * @return int The disk usage in bytes
     */
    public function getDiskUsage(): int;

    /**
     * Get all resources.
     *
     * @param int                                                            $limit   The maximum number of results to return
     * @param int                                                            $offset  The offset from which to return results from
     * @param list{0: string, 1?:array<string, string|list<string>>}|list{}  $where
     * @param list<list{0: string, 1: string}>|string|list{}                 $order
     * @param list<string>|list{}                                            $groupBy
     * @param list<list{0: string, 1: string, 2: string, 3?: string}>|list{} $join
     *
     * @return Paginator<T>
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
     * Remove the given resource from the database.
     *
     * @param AbstractFile $download the resource to be removed
     */
    public function remove(AbstractFile $download): void;

    /**
     * Get all resources with the given filters.
     *
     * @param array{keyword?: string} $filters
     *
     * @return Paginator<T>
     */
    public function getFiltered(array $filters, int $limit, int $offset, ?string $sortBy = 'title asc'): Paginator;
}
