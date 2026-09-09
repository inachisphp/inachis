<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\MaxExecutionTimeCheck;
use PHPUnit\Framework\TestCase;

final class MaxExecutionTimeCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new MaxExecutionTimeCheck();

        self::assertInstanceOf(
            MaxExecutionTimeCheck::class,
            $instance,
        );
    }
}
