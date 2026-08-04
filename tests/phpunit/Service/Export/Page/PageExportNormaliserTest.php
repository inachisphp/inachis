<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Export\Page;

use Inachis\Service\Export\Page\PageExportNormaliser;
use PHPUnit\Framework\TestCase;

final class PageExportNormaliserTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new PageExportNormaliser();

        self::assertInstanceOf(
            PageExportNormaliser::class,
            $instance
        );
    }
}