<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\System;

use Inachis\Entity\System\CspReport;
use PHPUnit\Framework\TestCase;

final class CspReportTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new CspReport();

        self::assertInstanceOf(
            CspReport::class,
            $instance
        );
    }
}