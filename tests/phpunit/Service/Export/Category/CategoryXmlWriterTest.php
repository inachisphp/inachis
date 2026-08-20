<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Export\Category;

use Inachis\Service\Export\Category\CategoryXmlWriter;
use PHPUnit\Framework\TestCase;

final class CategoryXmlWriterTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CategoryXmlWriter();

        self::assertInstanceOf(
            CategoryXmlWriter::class,
            $instance,
        );
    }
}
