<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Tools;

use Inachis\Controller\Page\Tools\AnalyticsController;
use PHPUnit\Framework\TestCase;

final class AnalyticsControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new AnalyticsController();

        self::assertInstanceOf(
            AnalyticsController::class,
            $instance,
        );
    }
}
