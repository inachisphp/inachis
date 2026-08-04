<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\SessionCookieLifetimeCheck;
use PHPUnit\Framework\TestCase;

final class SessionCookieLifetimeCheckTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new SessionCookieLifetimeCheck();

        self::assertInstanceOf(
            SessionCookieLifetimeCheck::class,
            $instance
        );
    }
}