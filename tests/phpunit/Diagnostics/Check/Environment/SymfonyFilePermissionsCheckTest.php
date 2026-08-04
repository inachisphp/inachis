<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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
            $instance
        );
    }
}