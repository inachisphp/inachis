<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\PhpOpcacheCheck;
use PHPUnit\Framework\TestCase;

final class PhpOpcacheCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new PhpOpcacheCheck();

        self::assertInstanceOf(
            PhpOpcacheCheck::class,
            $instance,
        );
    }
}
