<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Tools;

use Inachis\Controller\Page\Tools\ImportController;
use PHPUnit\Framework\TestCase;

final class ImportControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ImportController();

        self::assertInstanceOf(
            ImportController::class,
            $instance,
        );
    }
}
