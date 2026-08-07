<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\SessionCacheExpireCheck;
use PHPUnit\Framework\TestCase;

final class SessionCacheExpireCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SessionCacheExpireCheck();

        self::assertInstanceOf(
            SessionCacheExpireCheck::class,
            $instance,
        );
    }
}
