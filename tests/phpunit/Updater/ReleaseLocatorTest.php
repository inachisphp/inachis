<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater;

use Inachis\Updater\ReleaseInstance;
use Inachis\Updater\ReleaseLocator;
use PHPUnit\Framework\TestCase;

final class ReleaseLocatorTest extends TestCase
{
    private string $installRoot;

    private ReleaseLocator $locator;

    protected function setUp(): void
    {
        $this->installRoot = '/tmp/inachis';

        $this->locator = new ReleaseLocator(
            $this->installRoot
        );
    }

    public function testReturnsReleasesDirectory(): void
    {
        self::assertSame(
            '/tmp/inachis/releases',
            $this->locator->releasesDirectory()
        );
    }

    public function testReturnsSharedDirectory(): void
    {
        self::assertSame(
            '/tmp/inachis/shared',
            $this->locator->sharedDirectory()
        );
    }

    public function testReturnsCurrentLink(): void
    {
        self::assertSame(
            '/tmp/inachis/current',
            $this->locator->currentLink()
        );
    }

    public function testCreatesReleaseInstance(): void
    {
        $release = $this->locator->create('1.2.0');

        self::assertInstanceOf(
            ReleaseInstance::class,
            $release
        );

        self::assertSame(
            '1.2.0',
            $release->version
        );

        self::assertMatchesRegularExpression(
            '/^\d{14}-1\.2\.0$/',
            $release->identifier
        );

        self::assertSame(
            '/tmp/inachis/releases/'.$release->identifier,
            $release->path
        );
    }

    public function testCreateUsesDifferentVersions(): void
    {
        $release = $this->locator->create('2.0.0');

        self::assertSame(
            '2.0.0',
            $release->version
        );

        self::assertStringContainsString(
            '-2.0.0',
            $release->identifier
        );
    }
}
