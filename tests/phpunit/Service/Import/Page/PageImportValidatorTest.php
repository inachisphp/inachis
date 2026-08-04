<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Import\Page;

use Inachis\Service\Import\Page\PageImportValidator;
use PHPUnit\Framework\TestCase;

final class PageImportValidatorTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new PageImportValidator();

        self::assertInstanceOf(
            PageImportValidator::class,
            $instance
        );
    }
}