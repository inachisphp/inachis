<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Import\Series;

use Inachis\Service\Import\Series\SeriesImportResult;
use PHPUnit\Framework\TestCase;

final class SeriesImportResultTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new SeriesImportResult();

        self::assertInstanceOf(
            SeriesImportResult::class,
            $instance
        );
    }
}