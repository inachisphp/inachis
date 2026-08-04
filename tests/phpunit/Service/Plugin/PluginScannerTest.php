<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Plugin;

use Inachis\Service\Plugin\PluginScanner;
use PHPUnit\Framework\TestCase;

final class PluginScannerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new PluginScanner();

        self::assertInstanceOf(
            PluginScanner::class,
            $instance
        );
    }
}