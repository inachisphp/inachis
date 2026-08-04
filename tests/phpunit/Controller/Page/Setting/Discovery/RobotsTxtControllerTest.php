<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting\Discovery;

use Inachis\Controller\Page\Setting\Discovery\RobotsTxtController;
use PHPUnit\Framework\TestCase;

final class RobotsTxtControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new RobotsTxtController();

        self::assertInstanceOf(
            RobotsTxtController::class,
            $instance
        );
    }
}