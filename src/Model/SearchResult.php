<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Model;

/**
 * Model for containing search result items
 */
readonly class SearchResult implements \IteratorAggregate
{
    public function __construct(
        private array $results,
        private int   $total,
        private int   $offset,
        private int   $limit
    ) {}

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->results);
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
