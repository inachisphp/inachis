<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Enum;

use Inachis\Enum\DiffBlockType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiffBlockType::class)]
class DiffBlockTypeTest extends TestCase
{
    #[Test]
    public function enumContainsExpectedCases(): void
    {
        $cases = array_map(fn($case) => $case->value, DiffBlockType::cases());
        self::assertSame(
            ['unchanged', 'inserted', 'deleted', 'replaced'],
            $cases
        );
    }
}
