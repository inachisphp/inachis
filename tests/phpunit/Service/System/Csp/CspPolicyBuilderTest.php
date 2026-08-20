<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\System\Csp;

use Inachis\Service\System\Csp\CspPolicyBuilder;
use PHPUnit\Framework\TestCase;

final class CspPolicyBuilderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CspPolicyBuilder();

        self::assertInstanceOf(
            CspPolicyBuilder::class,
            $instance,
        );
    }
}
