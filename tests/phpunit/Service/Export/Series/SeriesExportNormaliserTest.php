<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Export\Series;

use Inachis\Service\Export\Series\SeriesExportNormaliser;
use PHPUnit\Framework\TestCase;

final class SeriesExportNormaliserTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new SeriesExportNormaliser();

        self::assertInstanceOf(
            SeriesExportNormaliser::class,
            $instance
        );
    }
}