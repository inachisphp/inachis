<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\OpCacheJitBufferSizeCheck;
use PHPUnit\Framework\TestCase;

final class OpCacheJitBufferSizeCheckTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new OpCacheJitBufferSizeCheck();

        self::assertInstanceOf(
            OpCacheJitBufferSizeCheck::class,
            $instance
        );
    }
}