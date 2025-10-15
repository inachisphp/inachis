<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Parser;

use App\Parser\ArrayToMarkdown;
use PHPUnit\Framework\TestCase;

class ArrayToMarkdownTest extends TestCase
{
    protected ArrayToMarkdown $parser;

    public function setUp(): void
    {
        $this->parser  = new ArrayToMarkdown();
        parent::setUp();
    }

    public function testParse(): void
    {
        $result = $this->parser->parse([
            'title' => 'A title',
            'subTitle' => 'Sub-title',
            'postDate' => '2025-01-01 01:23:45',
            'categories' => [
                ['fullPath' => 'Trips/Europe/Wales']
            ],
            'content' => 'This is a test',
        ]);
        $this->assertEquals($result, <<<MD
# A title
## Sub-title
2025-01-01 01:23:45
Trips/Europe/Wales


This is a test
MD
        );
    }
}
