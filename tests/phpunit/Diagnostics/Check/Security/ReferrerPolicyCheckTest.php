<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\ReferrerPolicyCheck;
use PHPUnit\Framework\TestCase;

final class ReferrerPolicyCheckTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ReferrerPolicyCheck();

        self::assertInstanceOf(
            ReferrerPolicyCheck::class,
            $instance
        );
    }
}