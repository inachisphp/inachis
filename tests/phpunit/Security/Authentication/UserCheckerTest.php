<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Security\Authentication;

use Inachis\Security\Authentication\UserChecker;
use PHPUnit\Framework\TestCase;

final class UserCheckerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new UserChecker();

        self::assertInstanceOf(
            UserChecker::class,
            $instance,
        );
    }
}
