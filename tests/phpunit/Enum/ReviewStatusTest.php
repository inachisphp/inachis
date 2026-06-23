<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Enum;

use Inachis\Enum\ReviewStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReviewStatus::class)]
class ReviewStatusTest extends TestCase
{
    #[Test]
    public function valuesReturnsAllPossibleValues(): void
    {
        self::assertSame(
            ['pending', 'approved', 'rejected'],
            ReviewStatus::values()
        );
    }

    #[Test]
    public function labelReturnsCorrectLabel(): void
    {
        self::assertSame('pending', ReviewStatus::PENDING->label());
        self::assertSame('approved', ReviewStatus::APPROVED->label());
        self::assertSame('rejected', ReviewStatus::REJECTED->label());
    }
}
