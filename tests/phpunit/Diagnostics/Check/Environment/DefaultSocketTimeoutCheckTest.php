<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\DefaultSocketTimeoutCheck;
use PHPUnit\Framework\TestCase;

final class DefaultSocketTimeoutCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new DefaultSocketTimeoutCheck();

        self::assertInstanceOf(
            DefaultSocketTimeoutCheck::class,
            $instance,
        );
    }
}
