<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\Check\Webserver\CacheHeadersCheck;
use PHPUnit\Framework\TestCase;

final class CacheHeadersCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CacheHeadersCheck();

        self::assertInstanceOf(
            CacheHeadersCheck::class,
            $instance,
        );
    }
}
