<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Model for containing search result items
 * @implements IteratorAggregate<int, array<string|int, mixed>>
 */
class SearchResult implements IteratorAggregate
{
    /**
     * Creates a new instance of {@link SearchResult}
     *
     * @param list<
     *     array{id: string, title: string, sub_title: string, content: string, type: string,
     *         contentDate: string, updatedAt: string, author: string, relevance: float}
     * > $results The search results
     * @param int $total The total number of search results
     * @param int $limit The limit of the search results
     * @param int $offset The offset of the search results
     */
    public function __construct(
        private array $results,
        private readonly int $total,
        private readonly int $limit,
        private readonly int $offset,
    ) {}

    /**
     * Returns an iterator for the search results
     *
     * @return Traversable<int, array<string|int, mixed>> The iterator for the search results
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
     * @return list<
     *     array{
     *         id: string, title: string, sub_title: string, content: string, type: string,
     *         contentDate: string, updatedAt: string, author: string, relevance: float
     *     }
     * > The search results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Updates a property of a specific search result
     *
     * @param int $key The key of the search result
     * @param string $property
     * @param mixed $value
     */
    public function updateResultPropertyByKey(int $key, string $property, mixed $value): void
    {
       if (!isset($this->results[$key])) {
            return;
        }

        match ($property) {
            'title',
            'sub_title',
            'content',
            'type',
            'author',
            'contentDate',
            'updatedAt',
            'url' => $this->updateStringProperty($key, $property, $value),

            'relevance' => $this->updateRelevanceProperty($key, $value),

            default => throw new \InvalidArgumentException(
                sprintf('Unknown property "%s"', $property)
            ),
        };
    }

    /**
     * Updates the properties of the result known to be a string
     *
     * @param int $key
     * @param string $property
     * @param mixed $value
     */
    private function updateStringProperty(int $key, string $property, mixed $value): void
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                sprintf('%s must be a string', $property)
            );
        }
        match ($property) {
            'title' => $this->results[$key]['title'] = $value,
            'sub_title' => $this->results[$key]['sub_title'] = $value,
            'content' => $this->results[$key]['content'] = $value,
            'type' => $this->results[$key]['type'] = $value,
            'author' => $this->results[$key]['author'] = $value,
            'contentDate' => $this->results[$key]['contentDate'] = $value,
            'updatedAt' => $this->results[$key]['updatedAt'] = $value,
            'url' => $this->results[$key]['url'] = $value,

            default => throw new \InvalidArgumentException(
                sprintf('Unknown property "%s"', $property)
            ),
        };
    }

    // private function updateAuthorProperty(
    //     string|int $key,
    //     mixed $value
    // ): void {
    //     if (
    //         $value !== null
    //         && !$value instanceof \Inachis\Entity\User\User
    //     ) {
    //         throw new \InvalidArgumentException(
    //             'author must be a User or null'
    //         );
    //     }

    //     $this->results[$key]['author'] = $value;
    // }

    /**
     * Updates the relevance property of the {@link SearchResult}
     *
     * @param int $key
     * @param mixed $value
     */
    private function updateRelevanceProperty(int $key, mixed $value): void {
        if (is_string($value)) {
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException(
                    'relevance string must be numeric'
                );
            }

            $value = (float) $value;
        }

        if (!is_float($value)) {
            throw new \InvalidArgumentException(
                'relevance must be a float or numeric string'
            );
        }

        $this->results[$key]['relevance'] = $value;
    }
}
