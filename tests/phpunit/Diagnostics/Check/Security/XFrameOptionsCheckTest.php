<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\XFrameOptionsCheck;
use PHPUnit\Framework\TestCase;

final class XFrameOptionsCheckTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new XFrameOptionsCheck();

        self::assertInstanceOf(
            XFrameOptionsCheck::class,
            $instance
        );
    }
}