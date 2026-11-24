<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\SessionHashBitsCheck;
use PHPUnit\Framework\TestCase;

final class SessionHashBitsCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SessionHashBitsCheck();

        self::assertInstanceOf(
            SessionHashBitsCheck::class,
            $instance,
        );
    }
}
