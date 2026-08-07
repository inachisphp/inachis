<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\Page;

use Inachis\Model\Page\CategoryPathDto;
use PHPUnit\Framework\TestCase;

final class CategoryPathDtoTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CategoryPathDto();

        self::assertInstanceOf(
            CategoryPathDto::class,
            $instance,
        );
    }
}
