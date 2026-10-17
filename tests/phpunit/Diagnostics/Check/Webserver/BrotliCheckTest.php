<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\Check\Webserver\BrotliCheck;
use PHPUnit\Framework\TestCase;

final class BrotliCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new BrotliCheck();

        self::assertInstanceOf(
            BrotliCheck::class,
            $instance,
        );
    }
}
