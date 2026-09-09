<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Exception;

use Inachis\Exception\InvalidContentSecurityPolicyException;
use PHPUnit\Framework\TestCase;

final class InvalidContentSecurityPolicyExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new InvalidContentSecurityPolicyException();

        self::assertInstanceOf(
            InvalidContentSecurityPolicyException::class,
            $instance,
        );
    }
}
