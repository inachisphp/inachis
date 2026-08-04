<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\Check\Webserver\Http2Check;
use PHPUnit\Framework\TestCase;

final class Http2CheckTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new Http2Check();

        self::assertInstanceOf(
            Http2Check::class,
            $instance
        );
    }
}