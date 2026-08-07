<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\TimezoneCheck;
use PHPUnit\Framework\TestCase;

final class TimezoneCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new TimezoneCheck();

        self::assertInstanceOf(
            TimezoneCheck::class,
            $instance,
        );
    }
}
