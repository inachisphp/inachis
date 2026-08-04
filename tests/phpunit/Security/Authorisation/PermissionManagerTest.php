<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Security\Authorisation;

use Inachis\Security\Authorisation\PermissionManager;
use PHPUnit\Framework\TestCase;

final class PermissionManagerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new PermissionManager();

        self::assertInstanceOf(
            PermissionManager::class,
            $instance
        );
    }
}