<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Model\Page;

use Inachis\Model\Page\ReviewThreadDto;
use PHPUnit\Framework\TestCase;

final class ReviewThreadDtoTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ReviewThreadDto();

        self::assertInstanceOf(
            ReviewThreadDto::class,
            $instance,
        );
    }
}
