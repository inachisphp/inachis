<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\User;

use Inachis\Entity\User\UserTrustedDevice;
use PHPUnit\Framework\TestCase;

final class UserTrustedDeviceTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new UserTrustedDevice();

        self::assertInstanceOf(
            UserTrustedDevice::class,
            $instance
        );
    }
}