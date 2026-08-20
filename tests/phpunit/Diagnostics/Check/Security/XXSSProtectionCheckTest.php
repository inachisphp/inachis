<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\XXSSProtectionCheck;
use PHPUnit\Framework\TestCase;

final class XXSSProtectionCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new XXSSProtectionCheck();

        self::assertInstanceOf(
            XXSSProtectionCheck::class,
            $instance,
        );
    }
}
