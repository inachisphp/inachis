<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\SecureCookieCheck;
use PHPUnit\Framework\TestCase;

final class SecureCookieCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SecureCookieCheck();

        self::assertInstanceOf(
            SecureCookieCheck::class,
            $instance,
        );
    }
}
