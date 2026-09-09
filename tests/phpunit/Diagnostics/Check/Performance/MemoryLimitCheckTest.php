<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\MemoryLimitCheck;
use PHPUnit\Framework\TestCase;

final class MemoryLimitCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new MemoryLimitCheck();

        self::assertInstanceOf(
            MemoryLimitCheck::class,
            $instance,
        );
    }
}
