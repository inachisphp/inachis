<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\HttpCompressionCheck;
use PHPUnit\Framework\TestCase;

final class HttpCompressionCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new HttpCompressionCheck();

        self::assertInstanceOf(
            HttpCompressionCheck::class,
            $instance,
        );
    }
}
