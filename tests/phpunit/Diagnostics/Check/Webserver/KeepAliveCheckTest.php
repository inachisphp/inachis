<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\Check\Webserver\KeepAliveCheck;
use PHPUnit\Framework\TestCase;

final class KeepAliveCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new KeepAliveCheck();

        self::assertInstanceOf(
            KeepAliveCheck::class,
            $instance,
        );
    }
}
