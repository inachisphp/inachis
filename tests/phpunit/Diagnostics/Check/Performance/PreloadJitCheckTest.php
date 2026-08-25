<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\PreloadJitCheck;
use PHPUnit\Framework\TestCase;

final class PreloadJitCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new PreloadJitCheck();

        self::assertInstanceOf(
            PreloadJitCheck::class,
            $instance,
        );
    }
}
