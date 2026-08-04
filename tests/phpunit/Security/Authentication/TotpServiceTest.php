<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Security\Authentication;

use Inachis\Security\Authentication\TotpService;
use PHPUnit\Framework\TestCase;

final class TotpServiceTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new TotpService();

        self::assertInstanceOf(
            TotpService::class,
            $instance
        );
    }
}