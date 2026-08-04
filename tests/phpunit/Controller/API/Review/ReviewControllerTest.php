<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\API\Review;

use Inachis\Controller\API\Review\ReviewController;
use PHPUnit\Framework\TestCase;

final class ReviewControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ReviewController();

        self::assertInstanceOf(
            ReviewController::class,
            $instance
        );
    }
}