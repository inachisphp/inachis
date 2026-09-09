<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\ServerModulesCheck;
use PHPUnit\Framework\TestCase;

final class ServerModulesCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ServerModulesCheck();

        self::assertInstanceOf(
            ServerModulesCheck::class,
            $instance,
        );
    }
}
