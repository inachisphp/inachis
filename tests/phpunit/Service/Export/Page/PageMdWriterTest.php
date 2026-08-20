<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Export\Page;

use Inachis\Service\Export\Page\PageMdWriter;
use PHPUnit\Framework\TestCase;

final class PageMdWriterTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new PageMdWriter();

        self::assertInstanceOf(
            PageMdWriter::class,
            $instance,
        );
    }
}
