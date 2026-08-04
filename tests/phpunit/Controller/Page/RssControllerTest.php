<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page;

use Inachis\Controller\Page\RssController;
use PHPUnit\Framework\TestCase;

final class RssControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new RssController();

        self::assertInstanceOf(
            RssController::class,
            $instance
        );
    }
}