<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Tools;

use Inachis\Controller\Page\Tools\CspReportController;
use PHPUnit\Framework\TestCase;

final class CspReportControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CspReportController();

        self::assertInstanceOf(
            CspReportController::class,
            $instance,
        );
    }
}
