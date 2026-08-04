<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Security;

use Inachis\Controller\Page\Security\TwoFactorAuthenticationController;
use PHPUnit\Framework\TestCase;

final class TwoFactorAuthenticationControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new TwoFactorAuthenticationController();

        self::assertInstanceOf(
            TwoFactorAuthenticationController::class,
            $instance
        );
    }
}