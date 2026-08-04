<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Inachis\Controller\Page\Admin\LoginActivityController;
use PHPUnit\Framework\TestCase;

final class LoginActivityControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new LoginActivityController();

        self::assertInstanceOf(
            LoginActivityController::class,
            $instance
        );
    }
}