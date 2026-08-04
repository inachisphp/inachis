<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Export\Page;

use Inachis\Service\Export\Page\PageXmlWriter;
use PHPUnit\Framework\TestCase;

final class PageXmlWriterTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new PageXmlWriter();

        self::assertInstanceOf(
            PageXmlWriter::class,
            $instance
        );
    }
}