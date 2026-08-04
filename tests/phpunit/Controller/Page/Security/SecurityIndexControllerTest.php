<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Security;

use Inachis\Controller\Page\Security\SecurityIndexController;
use PHPUnit\Framework\TestCase;

final class SecurityIndexControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new SecurityIndexController();

        self::assertInstanceOf(
            SecurityIndexController::class,
            $instance
        );
    }
}