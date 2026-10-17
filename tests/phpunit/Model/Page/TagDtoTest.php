<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Model\Page;

use Inachis\Model\Page\TagDto;
use PHPUnit\Framework\TestCase;

final class TagDtoTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new TagDto();

        self::assertInstanceOf(
            TagDto::class,
            $instance,
        );
    }
}
