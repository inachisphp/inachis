<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
            ['open', 'resolved', 'closed'],
            ReviewStatus::values(),
        );
    }

    #[Test]
    public function labelReturnsCorrectLabel(): void
    {
        self::assertSame('open', ReviewStatus::OPEN->label());
        self::assertSame('resolved', ReviewStatus::RESOLVED->label());
        self::assertSame('closed', ReviewStatus::CLOSED->label());
    }
}
