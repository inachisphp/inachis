<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Parser;

use Inachis\Service\Parser\ArrayToMarkdown;
use PHPUnit\Framework\TestCase;

class ArrayToMarkdownTest extends TestCase
{
    public function testParseFullPost(): void
    {
        $result = ArrayToMarkdown::parse([
            'title' => 'A title',
            'subTitle' => 'Sub-title',
            'content' => 'This is a test',
        ]);

        $this->assertSame(
            <<<MD
# A title
## Sub-title


This is a test
MD,
            $result,
        );
    }

    public function testParseWithoutSubtitle(): void
    {
        $result = ArrayToMarkdown::parse([
            'title' => 'A title',
            'content' => 'Body',
        ]);

        $this->assertSame(
            <<<MD
# A title


Body
MD,
            $result,
        );
    }

    public function testParseWithoutContent(): void
    {
        $result = ArrayToMarkdown::parse([
            'title' => 'A title',
            'subTitle' => 'Sub-title',
        ]);

        $this->assertSame(
            <<<MD
# A title
## Sub-title
MD,
            $result,
        );
    }

    public function testParseEmptyArray(): void
    {
        $this->assertSame('', ArrayToMarkdown::parse([]));
    }

    public function testParseOnlyContent(): void
    {
        $this->assertSame(
            PHP_EOL.PHP_EOL.'Body',
            ArrayToMarkdown::parse([
                'content' => 'Body',
            ]),
        );
    }
}
