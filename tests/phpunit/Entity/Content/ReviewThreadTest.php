<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Content;

use Inachis\Entity\Content\ReviewThread;
use PHPUnit\Framework\TestCase;

final class ReviewThreadTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ReviewThread();

        self::assertInstanceOf(
            ReviewThread::class,
            $instance
        );
    }
}