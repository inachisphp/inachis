<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Content\Page;

use Inachis\Service\Content\Page\ReviewRebaseService;
use PHPUnit\Framework\TestCase;

final class ReviewRebaseServiceTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ReviewRebaseService();

        self::assertInstanceOf(
            ReviewRebaseService::class,
            $instance,
        );
    }
}
