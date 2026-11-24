<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Model\Series;

use Inachis\Model\Series\SeriesExportDto;
use PHPUnit\Framework\TestCase;

final class SeriesExportDtoTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SeriesExportDto();

        self::assertInstanceOf(
            SeriesExportDto::class,
            $instance,
        );
    }
}
