<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\TempUploadDirectoryCheck;
use PHPUnit\Framework\TestCase;

final class TempUploadDirectoryCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new TempUploadDirectoryCheck();

        self::assertInstanceOf(
            TempUploadDirectoryCheck::class,
            $instance,
        );
    }
}
