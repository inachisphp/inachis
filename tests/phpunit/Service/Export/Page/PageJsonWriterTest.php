<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Export\Page;

use Inachis\Service\Export\Page\PageJsonWriter;
use PHPUnit\Framework\TestCase;

final class PageJsonWriterTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new PageJsonWriter();

        self::assertInstanceOf(
            PageJsonWriter::class,
            $instance
        );
    }
}