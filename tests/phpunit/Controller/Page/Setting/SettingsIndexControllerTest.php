<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting;

use Inachis\Controller\Page\Setting\SettingsIndexController;
use PHPUnit\Framework\TestCase;

final class SettingsIndexControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new SettingsIndexController();

        self::assertInstanceOf(
            SettingsIndexController::class,
            $instance
        );
    }
}