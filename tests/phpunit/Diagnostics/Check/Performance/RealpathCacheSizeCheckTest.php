<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\RealpathCacheSizeCheck;
use PHPUnit\Framework\TestCase;

final class RealpathCacheSizeCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new RealpathCacheSizeCheck();

        self::assertInstanceOf(
            RealpathCacheSizeCheck::class,
            $instance,
        );
    }
}
