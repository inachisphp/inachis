<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater;

use Inachis\Updater\ReleaseInstance;
use PHPUnit\Framework\TestCase;

final class ReleaseInstanceTest extends TestCase
{
    public function testCreatesReleaseInstance(): void
    {
        $release = new ReleaseInstance(
            identifier: '20260804073000-1.2.0',
            version: '1.2.0',
            path: '/var/www/inachis/releases/20260804073000-1.2.0',
        );

        self::assertSame(
            '20260804073000-1.2.0',
            $release->identifier
        );

        self::assertSame(
            '1.2.0',
            $release->version
        );

        self::assertSame(
            '/var/www/inachis/releases/20260804073000-1.2.0',
            $release->path
        );
    }

    public function testPropertiesAreReadonly(): void
    {
        $release = new ReleaseInstance(
            identifier: '20260804073000-1.2.0',
            version: '1.2.0',
            path: '/var/www/inachis/releases/20260804073000-1.2.0',
        );

        $this->expectException(\Error::class);

        $release->version = '2.0.0';
    }
}
