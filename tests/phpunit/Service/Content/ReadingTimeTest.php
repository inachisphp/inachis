<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Content;

use Inachis\Service\Content\ReadingTime;
use PHPUnit\Framework\TestCase;

final class ReadingTimeTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ReadingTime();

        self::assertInstanceOf(
            ReadingTime::class,
            $instance
        );
    }
}