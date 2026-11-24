<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\Image\Migration;

use Inachis\Service\Image\Migration\ImageMigrationReporter;
use PHPUnit\Framework\TestCase;

final class ImageMigrationReporterTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ImageMigrationReporter();

        self::assertInstanceOf(
            ImageMigrationReporter::class,
            $instance,
        );
    }
}
