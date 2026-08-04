<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\PermissionsPolicyCheck;
use PHPUnit\Framework\TestCase;

final class PermissionsPolicyCheckTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new PermissionsPolicyCheck();

        self::assertInstanceOf(
            PermissionsPolicyCheck::class,
            $instance
        );
    }
}