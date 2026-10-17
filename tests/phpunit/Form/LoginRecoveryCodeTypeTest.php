<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Form;

use Inachis\Form\LoginRecoveryCodeType;
use PHPUnit\Framework\TestCase;

final class LoginRecoveryCodeTypeTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new LoginRecoveryCodeType();

        self::assertInstanceOf(
            LoginRecoveryCodeType::class,
            $instance,
        );
    }
}
