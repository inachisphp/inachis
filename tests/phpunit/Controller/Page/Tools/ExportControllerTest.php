<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Tools;

use Inachis\Controller\Page\Tools\ExportController;
use PHPUnit\Framework\TestCase;

final class ExportControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ExportController();

        self::assertInstanceOf(
            ExportController::class,
            $instance,
        );
    }
}
