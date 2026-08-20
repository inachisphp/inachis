<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\FileCountCheck;
use PHPUnit\Framework\TestCase;

final class FileCountCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new FileCountCheck();

        self::assertInstanceOf(
            FileCountCheck::class,
            $instance,
        );
    }
}
