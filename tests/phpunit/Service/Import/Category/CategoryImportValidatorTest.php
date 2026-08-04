<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Import\Category;

use Inachis\Service\Import\Category\CategoryImportValidator;
use PHPUnit\Framework\TestCase;

final class CategoryImportValidatorTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new CategoryImportValidator();

        self::assertInstanceOf(
            CategoryImportValidator::class,
            $instance
        );
    }
}