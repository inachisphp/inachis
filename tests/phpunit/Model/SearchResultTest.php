<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Model;

use Inachis\Model\SearchResult;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SearchResultTest extends TestCase
{
    protected ?SearchResult $searchResult;

    public function setUp(): void
    {
        $this->searchResult = new SearchResult(
            [
                [
                    'title' => 'test',
                    'relevance' => 0.5,
                ],
                [
                    'title' => 'test2',
                    'relevance' => 0.1,
                ],
            ],
            2,
            0,
            3,
        );
        parent::setUp();
    }
    public function testGetIterator(): void
    {
        $results = $this->searchResult->getIterator();
        $this->assertIsIterable($results);
        $this->assertEquals('test', $results[0]['title']);
    }

    public function testGetTotal(): void
    {
        $this->assertEquals(2, $this->searchResult->getTotal());
    }

    public function testGetOffset(): void
    {
        $this->assertEquals(0, $this->searchResult->getOffset());
    }

    public function testGetLimit(): void
    {
        $this->assertEquals(3, $this->searchResult->getLimit());
    }
    public function testGetResults(): void
    {
        $results = $this->searchResult->getResults();
        $this->assertIsArray($results);
        $this->assertEquals('test', $results[0]['title']);
    }

    public function testUpdateResultPropertyByKey(): void
    {
        $this->searchResult->updateResultPropertyByKey(1, 'title', 'test2 edited');
        $results = $this->searchResult->getResults();
        $this->assertEquals('test', $results[0]['title']);
        $this->assertEquals('test2 edited', $results[1]['title']);
    }
}
