<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting\Discovery;

use Inachis\Controller\Page\Setting\Discovery\LlmsTxtWebController;
use PHPUnit\Framework\TestCase;

final class LlmsTxtWebControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new LlmsTxtWebController();

        self::assertInstanceOf(
            LlmsTxtWebController::class,
            $instance,
        );
    }
}
