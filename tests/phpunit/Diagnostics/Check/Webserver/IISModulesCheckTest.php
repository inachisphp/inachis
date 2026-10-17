<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\Check\Webserver\IISModulesCheck;
use PHPUnit\Framework\TestCase;

final class IISModulesCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new IISModulesCheck();

        self::assertInstanceOf(
            IISModulesCheck::class,
            $instance,
        );
    }
}
