<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\HttpsCheck;
use PHPUnit\Framework\TestCase;

final class HttpsCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new HttpsCheck();

        self::assertInstanceOf(
            HttpsCheck::class,
            $instance,
        );
    }
}
