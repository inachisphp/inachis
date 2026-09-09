<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Entity\User;

use Inachis\Entity\User\UserRecoveryCode;
use PHPUnit\Framework\TestCase;

final class UserRecoveryCodeTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new UserRecoveryCode();

        self::assertInstanceOf(
            UserRecoveryCode::class,
            $instance,
        );
    }
}
