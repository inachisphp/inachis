<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\XContentTypeOptionsCheck;
use PHPUnit\Framework\TestCase;

final class XContentTypeOptionsCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new XContentTypeOptionsCheck();

        self::assertInstanceOf(
            XContentTypeOptionsCheck::class,
            $instance,
        );
    }
}
