<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Export\Series;

use Inachis\Service\Export\Series\SeriesMdWriter;
use PHPUnit\Framework\TestCase;

final class SeriesMdWriterTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SeriesMdWriter();

        self::assertInstanceOf(
            SeriesMdWriter::class,
            $instance,
        );
    }
}
