<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Model for containing search result items
 * @template TValue of array<string, mixed>
 * @implements IteratorAggregate<int, TValue>
 */
class SearchResult implements IteratorAggregate
{
    /**
     * Creates a new instance of {@link SearchResult}
     * 
     * @param list<array<string, mixed>> $results The search results
     * @param int $total The total number of search results
     * @param int $offset The offset of the search results
     * @param int $limit The limit of the search results
     */
    public function __construct(
        private array $results,
        private readonly int $total,
        private readonly int $offset,
        private readonly int $limit
    ) {}

    /**
     * Returns an iterator for the search results
     * 
     * @return Traversable<int, TValue> The iterator for the search results
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }

    /**
     * Returns the total number of search results
     * 
     * @return int The total number of search results
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Returns the offset of the search results
     * 
     * @return int The offset of the search results
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Returns the limit of the search results
     * 
     * @return int The limit of the search results
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Returns the search results
     * 
     * @return array<int, array<string, TValue>> The search results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Updates a property of a specific search result
     * 
     * @param string|int $key The key of the search result
     * @param string $property The property to update
     * @param mixed $value The value to set
     * @return void
     */
    public function updateResultPropertyByKey(string|int $key, string $property, mixed $value): void
    {
        $this->results[$key][$property] = $value;
    }
}
