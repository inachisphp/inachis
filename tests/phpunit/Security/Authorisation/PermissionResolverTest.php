<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Security\Authorisation;

use Inachis\Security\Authorisation\PermissionResolver;
use PHPUnit\Framework\TestCase;

final class PermissionResolverTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new PermissionResolver();

        self::assertInstanceOf(
            PermissionResolver::class,
            $instance,
        );
    }
}
