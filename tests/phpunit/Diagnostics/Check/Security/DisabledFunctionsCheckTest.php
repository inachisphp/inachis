<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\DisabledFunctionsCheck;
use PHPUnit\Framework\TestCase;

final class DisabledFunctionsCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new DisabledFunctionsCheck();

        self::assertInstanceOf(
            DisabledFunctionsCheck::class,
            $instance,
        );
    }
}
