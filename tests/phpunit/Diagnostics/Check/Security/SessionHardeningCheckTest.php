<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\SessionHardeningCheck;
use PHPUnit\Framework\TestCase;

final class SessionHardeningCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SessionHardeningCheck();

        self::assertInstanceOf(
            SessionHardeningCheck::class,
            $instance,
        );
    }
}
