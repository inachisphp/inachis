<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\User;

use Inachis\Entity\User\UserTotp;
use PHPUnit\Framework\TestCase;

final class UserTotpTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new UserTotp();

        self::assertInstanceOf(
            UserTotp::class,
            $instance
        );
    }
}