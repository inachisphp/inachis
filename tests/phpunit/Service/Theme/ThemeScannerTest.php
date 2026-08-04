<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Theme;

use Inachis\Service\Theme\ThemeScanner;
use PHPUnit\Framework\TestCase;

final class ThemeScannerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ThemeScanner();

        self::assertInstanceOf(
            ThemeScanner::class,
            $instance
        );
    }
}