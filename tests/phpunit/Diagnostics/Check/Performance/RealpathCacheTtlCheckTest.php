<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\RealpathCacheTtlCheck;
use PHPUnit\Framework\TestCase;

final class RealpathCacheTtlCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new RealpathCacheTtlCheck();

        self::assertInstanceOf(
            RealpathCacheTtlCheck::class,
            $instance,
        );
    }
}
