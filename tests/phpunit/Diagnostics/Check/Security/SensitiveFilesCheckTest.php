<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\SensitiveFilesCheck;
use PHPUnit\Framework\TestCase;

final class SensitiveFilesCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SensitiveFilesCheck();

        self::assertInstanceOf(
            SensitiveFilesCheck::class,
            $instance,
        );
    }
}
