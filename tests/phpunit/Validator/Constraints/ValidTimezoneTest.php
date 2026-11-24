<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Validator\Constraints;

use Inachis\Validator\Constraints\ValidTimezone;
use PHPUnit\Framework\TestCase;

final class ValidTimezoneTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ValidTimezone();

        self::assertInstanceOf(
            ValidTimezone::class,
            $instance,
        );
    }
}
