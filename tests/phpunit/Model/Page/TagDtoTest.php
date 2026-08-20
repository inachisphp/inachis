<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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
