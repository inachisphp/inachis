<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting;

use Inachis\Controller\Page\Setting\ThemeController;
use PHPUnit\Framework\TestCase;

final class ThemeControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ThemeController();

        self::assertInstanceOf(
            ThemeController::class,
            $instance
        );
    }
}