<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Export\Series;

use Inachis\Service\Export\Series\SeriesXmlWriter;
use PHPUnit\Framework\TestCase;

final class SeriesXmlWriterTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new SeriesXmlWriter();

        self::assertInstanceOf(
            SeriesXmlWriter::class,
            $instance
        );
    }
}