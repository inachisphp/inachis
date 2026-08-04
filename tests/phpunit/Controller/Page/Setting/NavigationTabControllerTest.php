<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting;

use Inachis\Controller\Page\Setting\NavigationTabController;
use PHPUnit\Framework\TestCase;

final class NavigationTabControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new NavigationTabController();

        self::assertInstanceOf(
            NavigationTabController::class,
            $instance
        );
    }
}