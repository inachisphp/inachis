<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\System\Csp;

use Inachis\Service\System\Csp\CspNoiseFilter;
use PHPUnit\Framework\TestCase;

final class CspNoiseFilterTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CspNoiseFilter();

        self::assertInstanceOf(
            CspNoiseFilter::class,
            $instance,
        );
    }
}
