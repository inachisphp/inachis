<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

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
