<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\DisplayErrorsCheck;
use PHPUnit\Framework\TestCase;

final class DisplayErrorsCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new DisplayErrorsCheck();

        self::assertInstanceOf(
            DisplayErrorsCheck::class,
            $instance,
        );
    }
}
