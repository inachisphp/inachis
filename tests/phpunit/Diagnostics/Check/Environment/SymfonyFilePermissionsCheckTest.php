<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\SymfonyFilePermissionsCheck;
use PHPUnit\Framework\TestCase;

final class SymfonyFilePermissionsCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SymfonyFilePermissionsCheck();

        self::assertInstanceOf(
            SymfonyFilePermissionsCheck::class,
            $instance,
        );
    }
}
