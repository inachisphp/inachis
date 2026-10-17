<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\Import;

use Inachis\Service\Import\ImportDetector;
use PHPUnit\Framework\TestCase;

final class ImportDetectorTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ImportDetector();

        self::assertInstanceOf(
            ImportDetector::class,
            $instance,
        );
    }
}
