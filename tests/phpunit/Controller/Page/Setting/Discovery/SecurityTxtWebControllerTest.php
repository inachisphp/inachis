<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting\Discovery;

use Inachis\Controller\Page\Setting\Discovery\SecurityTxtWebController;
use PHPUnit\Framework\TestCase;

final class SecurityTxtWebControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new SecurityTxtWebController();

        self::assertInstanceOf(
            SecurityTxtWebController::class,
            $instance
        );
    }
}