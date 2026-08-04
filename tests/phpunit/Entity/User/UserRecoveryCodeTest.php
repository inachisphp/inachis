<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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
            $instance
        );
    }
}