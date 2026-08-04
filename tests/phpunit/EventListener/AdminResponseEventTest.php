<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\EventListener;

use Inachis\EventListener\AdminResponseEvent;
use PHPUnit\Framework\TestCase;

final class AdminResponseEventTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new AdminResponseEvent();

        self::assertInstanceOf(
            AdminResponseEvent::class,
            $instance
        );
    }
}