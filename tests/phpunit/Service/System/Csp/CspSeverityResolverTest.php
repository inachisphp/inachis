<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\System\Csp;

use Inachis\Service\System\Csp\CspSeverityResolver;
use PHPUnit\Framework\TestCase;

final class CspSeverityResolverTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new CspSeverityResolver();

        self::assertInstanceOf(
            CspSeverityResolver::class,
            $instance
        );
    }
}