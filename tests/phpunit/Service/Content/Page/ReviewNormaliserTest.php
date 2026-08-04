<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Content\Page;

use Inachis\Service\Content\Page\ReviewNormaliser;
use PHPUnit\Framework\TestCase;

final class ReviewNormaliserTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ReviewNormaliser();

        self::assertInstanceOf(
            ReviewNormaliser::class,
            $instance
        );
    }
}