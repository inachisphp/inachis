<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater;

use Inachis\Updater\ReleaseExtractor;
use PHPUnit\Framework\TestCase;

final class ReleaseExtractorTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ReleaseExtractor();

        self::assertInstanceOf(
            ReleaseExtractor::class,
            $instance
        );
    }
}