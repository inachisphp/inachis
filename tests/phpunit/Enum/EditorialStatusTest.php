<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Enum;

use Inachis\Enum\EditorialStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EditorialStatus::class)]
class EditorialStatusTest extends TestCase
{
    #[Test]
    public function valuesReturnsAllPossibleValues(): void
    {
        self::assertSame(
            ['draft', 'review', 'published',],
            EditorialStatus::values()
        );
    }

    #[Test]
    public function labelReturnsCorrectLabel(): void
    {
        self::assertSame('Draft', EditorialStatus::DRAFT->label());
        self::assertSame('In Review', EditorialStatus::REVIEW->label());
        self::assertSame('Published', EditorialStatus::PUBLISHED->label());
    }
}
