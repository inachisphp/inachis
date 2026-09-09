<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Model\Import;

use Inachis\Model\Import\CategoryImportResult;
use PHPUnit\Framework\TestCase;

final class CategoryImportResultTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CategoryImportResult();

        self::assertInstanceOf(
            CategoryImportResult::class,
            $instance,
        );
    }
}
