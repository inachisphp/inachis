<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting\Discovery;

use Inachis\Controller\Page\Setting\Discovery\LlmsTxtController;
use PHPUnit\Framework\TestCase;

final class LlmsTxtControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new LlmsTxtController();

        self::assertInstanceOf(
            LlmsTxtController::class,
            $instance,
        );
    }
}
