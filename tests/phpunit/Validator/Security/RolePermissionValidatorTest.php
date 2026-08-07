<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Validator\Security;

use Inachis\Validator\Security\RolePermissionValidator;
use PHPUnit\Framework\TestCase;

final class RolePermissionValidatorTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new RolePermissionValidator();

        self::assertInstanceOf(
            RolePermissionValidator::class,
            $instance,
        );
    }
}
