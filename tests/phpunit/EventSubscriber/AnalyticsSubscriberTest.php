<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\EventSubscriber;

use Inachis\EventSubscriber\AnalyticsSubscriber;
use PHPUnit\Framework\TestCase;

final class AnalyticsSubscriberTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new AnalyticsSubscriber();

        self::assertInstanceOf(
            AnalyticsSubscriber::class,
            $instance,
        );
    }
}
