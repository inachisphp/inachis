<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\AllowUrlFopenCheck;
use PHPUnit\Framework\TestCase;

final class AllowUrlFopenCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new AllowUrlFopenCheck();

        self::assertInstanceOf(
            AllowUrlFopenCheck::class,
            $instance,
        );
    }
}
