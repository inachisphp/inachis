<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\System\Domain;

use Inachis\Service\System\Domain\NativeDnsResolver;
use PHPUnit\Framework\TestCase;

final class NativeDnsResolverTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new NativeDnsResolver();

        self::assertInstanceOf(
            NativeDnsResolver::class,
            $instance,
        );
    }
}
