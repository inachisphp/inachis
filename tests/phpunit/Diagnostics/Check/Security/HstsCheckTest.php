<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\HstsCheck;
use PHPUnit\Framework\TestCase;

final class HstsCheckTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new HstsCheck();

        self::assertInstanceOf(
            HstsCheck::class,
            $instance
        );
    }
}