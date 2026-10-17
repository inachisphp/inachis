<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\System\Csp;

use Inachis\Service\System\Csp\CspReportDtoFactory;
use PHPUnit\Framework\TestCase;

final class CspReportDtoFactoryTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CspReportDtoFactory();

        self::assertInstanceOf(
            CspReportDtoFactory::class,
            $instance,
        );
    }
}
