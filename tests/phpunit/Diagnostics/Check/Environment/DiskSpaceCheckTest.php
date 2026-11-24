<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\DiskSpaceCheck;
use PHPUnit\Framework\TestCase;

final class DiskSpaceCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new DiskSpaceCheck();

        self::assertInstanceOf(
            DiskSpaceCheck::class,
            $instance,
        );
    }
}
