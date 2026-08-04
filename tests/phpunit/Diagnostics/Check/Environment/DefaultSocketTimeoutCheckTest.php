<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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
            $instance
        );
    }
}