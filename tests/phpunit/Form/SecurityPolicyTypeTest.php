<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Form\SecurityPolicyType;
use PHPUnit\Framework\TestCase;

final class SecurityPolicyTypeTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SecurityPolicyType();

        self::assertInstanceOf(
            SecurityPolicyType::class,
            $instance,
        );
    }
}
