<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Security;

use Inachis\Controller\Page\Security\PrivacyController;
use PHPUnit\Framework\TestCase;

final class PrivacyControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new PrivacyController();

        self::assertInstanceOf(
            PrivacyController::class,
            $instance,
        );
    }
}
