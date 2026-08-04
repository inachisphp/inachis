<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model;

use Inachis\Model\CategoryExportDto;
use PHPUnit\Framework\TestCase;

final class CategoryExportDtoTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new CategoryExportDto();

        self::assertInstanceOf(
            CategoryExportDto::class,
            $instance
        );
    }
}