<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\SessionHandlerCheck;
use PHPUnit\Framework\TestCase;

final class SessionHandlerCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SessionHandlerCheck();

        self::assertInstanceOf(
            SessionHandlerCheck::class,
            $instance,
        );
    }
}
