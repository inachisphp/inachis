<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\System;

use Inachis\Model\System\PageMetadata;
use PHPUnit\Framework\TestCase;

final class PageMetadataTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new PageMetadata();

        self::assertInstanceOf(
            PageMetadata::class,
            $instance,
        );
    }
}
