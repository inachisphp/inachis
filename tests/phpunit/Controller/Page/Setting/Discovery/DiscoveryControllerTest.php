<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting\Discovery;

use Inachis\Controller\Page\Setting\Discovery\DiscoveryController;
use PHPUnit\Framework\TestCase;

final class DiscoveryControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new DiscoveryController();

        self::assertInstanceOf(
            DiscoveryController::class,
            $instance,
        );
    }
}
