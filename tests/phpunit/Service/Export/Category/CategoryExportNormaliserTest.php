<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\Export\Category;

use Inachis\Service\Export\Category\CategoryExportNormaliser;
use PHPUnit\Framework\TestCase;

final class CategoryExportNormaliserTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CategoryExportNormaliser();

        self::assertInstanceOf(
            CategoryExportNormaliser::class,
            $instance,
        );
    }
}
