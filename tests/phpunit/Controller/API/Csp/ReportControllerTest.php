<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\API\Csp;

use Inachis\Controller\API\Csp\ReportController;
use PHPUnit\Framework\TestCase;

final class ReportControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ReportController();

        self::assertInstanceOf(
            ReportController::class,
            $instance
        );
    }
}