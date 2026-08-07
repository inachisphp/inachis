<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Inachis\Controller\Page\Admin\UserTrustedDevicesController;
use PHPUnit\Framework\TestCase;

final class UserTrustedDevicesControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new UserTrustedDevicesController();

        self::assertInstanceOf(
            UserTrustedDevicesController::class,
            $instance,
        );
    }
}
