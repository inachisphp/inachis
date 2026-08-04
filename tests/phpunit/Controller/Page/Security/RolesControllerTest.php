<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Security;

use Inachis\Controller\Page\Security\RolesController;
use PHPUnit\Framework\TestCase;

final class RolesControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new RolesController();

        self::assertInstanceOf(
            RolesController::class,
            $instance
        );
    }
}