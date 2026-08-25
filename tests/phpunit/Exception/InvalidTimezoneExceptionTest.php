<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Exception;

use Inachis\Exception\InvalidTimezoneException;
use PHPUnit\Framework\TestCase;

final class InvalidTimezoneExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new InvalidTimezoneException();

        self::assertInstanceOf(
            InvalidTimezoneException::class,
            $instance,
        );
    }
}
