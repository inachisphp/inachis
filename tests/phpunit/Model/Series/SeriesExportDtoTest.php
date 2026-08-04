<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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
            $instance
        );
    }
}