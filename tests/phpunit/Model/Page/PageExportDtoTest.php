<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\Page;

use Inachis\Model\Page\PageExportDto;
use PHPUnit\Framework\TestCase;

final class PageExportDtoTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new PageExportDto();

        self::assertInstanceOf(
            PageExportDto::class,
            $instance
        );
    }
}