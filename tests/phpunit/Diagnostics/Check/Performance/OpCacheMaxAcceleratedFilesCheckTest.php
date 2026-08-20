<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\OpCacheMaxAcceleratedFilesCheck;
use PHPUnit\Framework\TestCase;

final class OpCacheMaxAcceleratedFilesCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new OpCacheMaxAcceleratedFilesCheck();

        self::assertInstanceOf(
            OpCacheMaxAcceleratedFilesCheck::class,
            $instance,
        );
    }
}
