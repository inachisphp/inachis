<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Tools;

use Inachis\Controller\Page\Tools\AnalyticsSecurityController;
use PHPUnit\Framework\TestCase;

final class AnalyticsSecurityControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new AnalyticsSecurityController();

        self::assertInstanceOf(
            AnalyticsSecurityController::class,
            $instance
        );
    }
}