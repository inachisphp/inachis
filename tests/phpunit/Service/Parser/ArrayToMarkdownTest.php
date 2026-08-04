<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Parser;

use Inachis\Service\Parser\ArrayToMarkdown;
use PHPUnit\Framework\TestCase;

final class ArrayToMarkdownTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ArrayToMarkdown();

        self::assertInstanceOf(
            ArrayToMarkdown::class,
            $instance
        );
    }
}