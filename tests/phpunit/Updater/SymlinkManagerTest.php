<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater;

use Inachis\Updater\SymlinkManager;
use PHPUnit\Framework\TestCase;

final class SymlinkManagerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new SymlinkManager();

        self::assertInstanceOf(
            SymlinkManager::class,
            $instance
        );
    }
}