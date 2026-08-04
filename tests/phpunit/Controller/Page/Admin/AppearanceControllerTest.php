<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Inachis\Controller\Page\Admin\AppearanceController;
use PHPUnit\Framework\TestCase;

final class AppearanceControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new AppearanceController();

        self::assertInstanceOf(
            AppearanceController::class,
            $instance
        );
    }
}