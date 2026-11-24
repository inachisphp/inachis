<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Exception\Updater;

use Inachis\Exception\Updater\IncompatibleVersionException;
use PHPUnit\Framework\TestCase;

final class IncompatibleVersionExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new IncompatibleVersionException();

        self::assertInstanceOf(
            IncompatibleVersionException::class,
            $instance,
        );
    }
}
