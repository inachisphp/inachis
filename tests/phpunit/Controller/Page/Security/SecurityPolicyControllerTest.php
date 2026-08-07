<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Security;

use Inachis\Controller\Page\Security\SecurityPolicyController;
use PHPUnit\Framework\TestCase;

final class SecurityPolicyControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SecurityPolicyController();

        self::assertInstanceOf(
            SecurityPolicyController::class,
            $instance,
        );
    }
}
