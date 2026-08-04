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
    private string $tempDirectory;

    protected function setUp(): void
    {
        $this->tempDirectory = sys_get_temp_dir()
            .'/inachis_symlink_test_'.uniqid('', true);

        mkdir($this->tempDirectory, 0775, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDirectory)) {
            $this->removeDirectory($this->tempDirectory);
        }
    }

    public function testSwitchCurrentCreatesSymlink(): void
    {
        $manager = new SymlinkManager();

        $release = $this->tempDirectory.'/release';
        $current = $this->tempDirectory.'/current';

        mkdir($release);

        $manager->switchCurrent(
            $current,
            $release
        );

        self::assertTrue(
            is_link($current)
        );

        self::assertSame(
            $release,
            readlink($current)
        );
    }

    public function testSwitchCurrentThrowsWhenTargetCannotBeCreated(): void
    {
        $manager = new SymlinkManager();

        $current = $this->tempDirectory.'/missing/current';
        $target = $this->tempDirectory.'/release';

        mkdir($target);

        $this->expectException(\RuntimeException::class);

        $manager->switchCurrent(
            $current,
            $target
        );
    }

    public function testLinksSharedResources(): void
    {
        $manager = new SymlinkManager();

        $release = $this->tempDirectory.'/release';
        $shared = $this->tempDirectory.'/shared';

        mkdir($release);
        mkdir($shared, 0775, true);

        file_put_contents(
            $shared.'/config.yaml',
            'test'
        );

        $manager->linkSharedResources(
            $release,
            [
                'config/config.yaml' => $shared.'/config.yaml',
            ]
        );

        $link = $release.'/config/config.yaml';

        self::assertTrue(
            is_link($link)
        );

        self::assertSame(
            $shared.'/config.yaml',
            readlink($link)
        );
    }

    public function testLinksSharedResourcesReplacingExistingFile(): void
    {
        $manager = new SymlinkManager();

        $release = $this->tempDirectory.'/release';
        $shared = $this->tempDirectory.'/shared';

        mkdir($release.'/config', 0775, true);
        mkdir($shared, 0775, true);

        file_put_contents(
            $release.'/config/config.yaml',
            'placeholder'
        );

        file_put_contents(
            $shared.'/config.yaml',
            'shared'
        );

        $manager->linkSharedResources(
            $release,
            [
                'config/config.yaml' => $shared.'/config.yaml',
            ]
        );

        self::assertTrue(
            is_link($release.'/config/config.yaml')
        );

        self::assertSame(
            $shared.'/config.yaml',
            readlink($release.'/config/config.yaml')
        );
    }

    public function testLinkSharedResourcesCreatesMissingSharedParentDirectory(): void
    {
        $manager = new SymlinkManager();

        $release = $this->tempDirectory.'/release';
        $shared = $this->tempDirectory.'/shared/uploads/image.png';

        mkdir($release, 0775, true);

        file_put_contents(
            $this->tempDirectory.'/source.png',
            'image'
        );

        $manager->linkSharedResources(
            $release,
            [
                'uploads/image.png' => $shared,
            ]
        );

        self::assertTrue(
            is_link($release.'/uploads/image.png')
        );
    }

    private function removeDirectory(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
