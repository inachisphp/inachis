<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Security;

use Inachis\Controller\Page\Security\RecoveryCodeController;
use PHPUnit\Framework\TestCase;

final class RecoveryCodeControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new RecoveryCodeController();

        self::assertInstanceOf(
            RecoveryCodeController::class,
            $instance
        );
    }
}