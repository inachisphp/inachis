<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

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
