<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model;

use Inachis\Model\SearchResult;
use PHPUnit\Framework\TestCase;

class SearchResultTest extends TestCase
{
    private SearchResult $searchResult;

    protected function setUp(): void
    {
        $this->searchResult = new SearchResult(
            [
                [
                    'id' => '1',
                    'title' => 'test',
                    'sub_title' => '',
                    'content' => '',
                    'type' => 'page',
                    'contentDate' => '2025-01-01',
                    'updatedAt' => '2025-01-01',
                    'author' => 'David',
                    'url' => '/test',
                    'relevance' => 0.5,
                ],
                [
                    'id' => '2',
                    'title' => 'test2',
                    'sub_title' => '',
                    'content' => '',
                    'type' => 'page',
                    'contentDate' => '2025-01-02',
                    'updatedAt' => '2025-01-02',
                    'author' => 'David',
                    'url' => '/test2',
                    'relevance' => 0.1,
                ],
            ],
            2,
            3,
            0,
        );
    }

    public function testGetIterator(): void
    {
        $results = iterator_to_array($this->searchResult);

        $this->assertCount(2, $results);
        $this->assertSame('test', $results[0]['title']);
    }

    public function testGetTotal(): void
    {
        $this->assertSame(2, $this->searchResult->getTotal());
    }

    public function testGetOffset(): void
    {
        $this->assertSame(0, $this->searchResult->getOffset());
    }

    public function testGetLimit(): void
    {
        $this->assertSame(3, $this->searchResult->getLimit());
    }

    public function testGetResults(): void
    {
        $results = $this->searchResult->getResults();

        $this->assertCount(2, $results);
        $this->assertSame('test', $results[0]['title']);
    }

    public function testUpdateResultPropertyByKey(): void
    {
        $this->searchResult->updateResultPropertyByKey(1, 'title', 'edited');
        $this->searchResult->updateResultPropertyByKey(1, 'sub_title', 'subtitle');
        $this->searchResult->updateResultPropertyByKey(1, 'content', 'content');
        $this->searchResult->updateResultPropertyByKey(1, 'type', 'series');
        $this->searchResult->updateResultPropertyByKey(1, 'author', 'John');
        $this->searchResult->updateResultPropertyByKey(1, 'contentDate', '2025-02-01');
        $this->searchResult->updateResultPropertyByKey(1, 'updatedAt', '2025-02-02');
        $this->searchResult->updateResultPropertyByKey(1, 'url', '/edited');
        $this->searchResult->updateResultPropertyByKey(1, 'relevance', '0.85');

        $result = $this->searchResult->getResults()[1];

        $this->assertSame('edited', $result['title']);
        $this->assertSame('subtitle', $result['sub_title']);
        $this->assertSame('content', $result['content']);
        $this->assertSame('series', $result['type']);
        $this->assertSame('John', $result['author']);
        $this->assertSame('2025-02-01', $result['contentDate']);
        $this->assertSame('/edited', $result['url']);
        $this->assertSame(0.85, $result['relevance']);
    }

    public function testUpdateIgnoresMissingKey(): void
    {
        $before = $this->searchResult->getResults();

        $this->searchResult->updateResultPropertyByKey(
            99,
            'title',
            'ignored'
        );

        $this->assertSame($before, $this->searchResult->getResults());
    }

    public function testStringPropertyMustBeString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('title must be a string');

        $this->searchResult->updateResultPropertyByKey(
            0,
            'title',
            123
        );
    }

    public function testUnknownPropertyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown property "unknown"');

        $this->searchResult->updateResultPropertyByKey(
            0,
            'unknown',
            'value'
        );
    }

    public function testRelevanceMustBeNumericString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('relevance string must be numeric');

        $this->searchResult->updateResultPropertyByKey(
            0,
            'relevance',
            'abc'
        );
    }

    public function testRelevanceMustBeFloatOrNumericString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'relevance must be a float or numeric string'
        );

        $this->searchResult->updateResultPropertyByKey(
            0,
            'relevance',
            1
        );
    }

    public function testRelevanceAcceptsFloat(): void
    {
        $this->searchResult->updateResultPropertyByKey(
            0,
            'relevance',
            0.99
        );

        $this->assertSame(
            0.99,
            $this->searchResult->getResults()[0]['relevance']
        );
    }
}
