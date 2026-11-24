<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\PhpExtensionsCheck;
use PHPUnit\Framework\TestCase;

final class PhpExtensionsCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new PhpExtensionsCheck();

        self::assertInstanceOf(
            PhpExtensionsCheck::class,
            $instance,
        );
    }
}
