<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Tools;

use Inachis\Controller\Page\Tools\MaintenanceController;
use PHPUnit\Framework\TestCase;

final class MaintenanceControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new MaintenanceController();

        self::assertInstanceOf(
            MaintenanceController::class,
            $instance,
        );
    }
}
