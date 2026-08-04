<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\OpCacheJitCheck;
use PHPUnit\Framework\TestCase;

final class OpCacheJitCheckTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new OpCacheJitCheck();

        self::assertInstanceOf(
            OpCacheJitCheck::class,
            $instance
        );
    }
}