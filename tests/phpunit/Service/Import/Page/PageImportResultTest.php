<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Import\Page;

use Inachis\Service\Import\Page\PageImportResult;
use PHPUnit\Framework\TestCase;

final class PageImportResultTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new PageImportResult();

        self::assertInstanceOf(
            PageImportResult::class,
            $instance,
        );
    }
}
