<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Validator\Constraints;

use Inachis\Validator\Constraints\ValidIPAddress;
use PHPUnit\Framework\TestCase;

final class ValidIPAddressTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ValidIPAddress();

        self::assertInstanceOf(
            ValidIPAddress::class,
            $instance,
        );
    }
}
