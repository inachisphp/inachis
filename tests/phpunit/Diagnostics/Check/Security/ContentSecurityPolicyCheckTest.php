<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\ContentSecurityPolicyCheck;
use PHPUnit\Framework\TestCase;

final class ContentSecurityPolicyCheckTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ContentSecurityPolicyCheck();

        self::assertInstanceOf(
            ContentSecurityPolicyCheck::class,
            $instance,
        );
    }
}
