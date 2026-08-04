<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Formatting;

use Inachis\Service\Formatting\NumberFormatter;
use PHPUnit\Framework\TestCase;

final class NumberFormatterTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new NumberFormatter();

        self::assertInstanceOf(
            NumberFormatter::class,
            $instance
        );
    }
}