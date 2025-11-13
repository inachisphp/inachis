<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Model;

use App\Model\SearchResult;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SearchResultTest extends TestCase
{
    protected ?SearchResult $searchResult;

    public function setUp(): void
    {
        $this->searchResult = new SearchResult();
        parent::setUp();
    }
}
