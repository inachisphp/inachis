<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Tools;

use Inachis\Controller\Page\Tools\ToolsIndexController;
use PHPUnit\Framework\TestCase;

final class ToolsIndexControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ToolsIndexController();

        self::assertInstanceOf(
            ToolsIndexController::class,
            $instance
        );
    }
}