<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\SessionCookieOnlyCheck;
use PHPUnit\Framework\TestCase;

final class SessionCookieOnlyCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SessionCookieOnlyCheck();

        self::assertInstanceOf(
            SessionCookieOnlyCheck::class,
            $instance,
        );
    }
}
