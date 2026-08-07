<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\SessionTransSidCheck;
use PHPUnit\Framework\TestCase;

final class SessionTransSidCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SessionTransSidCheck();

        self::assertInstanceOf(
            SessionTransSidCheck::class,
            $instance,
        );
    }
}
