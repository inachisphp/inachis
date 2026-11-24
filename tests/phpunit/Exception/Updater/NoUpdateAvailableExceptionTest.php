<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Exception\Updater;

use Inachis\Exception\Updater\NoUpdateAvailableException;
use PHPUnit\Framework\TestCase;

final class NoUpdateAvailableExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new NoUpdateAvailableException();

        self::assertInstanceOf(
            NoUpdateAvailableException::class,
            $instance,
        );
    }
}
