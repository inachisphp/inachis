<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\WebrootContainmentCheck;
use PHPUnit\Framework\TestCase;

final class WebrootContainmentCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new WebrootContainmentCheck();

        self::assertInstanceOf(
            WebrootContainmentCheck::class,
            $instance,
        );
    }
}
