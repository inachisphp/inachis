<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\DebugProfilerCheck;
use PHPUnit\Framework\TestCase;

final class DebugProfilerCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new DebugProfilerCheck();

        self::assertInstanceOf(
            DebugProfilerCheck::class,
            $instance,
        );
    }
}
