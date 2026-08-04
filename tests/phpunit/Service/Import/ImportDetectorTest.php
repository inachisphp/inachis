<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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
            $instance
        );
    }
}