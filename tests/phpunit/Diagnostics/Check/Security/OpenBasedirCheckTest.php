<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\OpenBasedirCheck;
use PHPUnit\Framework\TestCase;

final class OpenBasedirCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new OpenBasedirCheck();

        self::assertInstanceOf(
            OpenBasedirCheck::class,
            $instance,
        );
    }
}
