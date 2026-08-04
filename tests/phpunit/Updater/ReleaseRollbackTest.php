<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater;

use Inachis\Service\System\Maintenance\MaintenanceManager;
use Inachis\Updater\ReleaseInstance;
use Inachis\Updater\ReleaseLocator;
use Inachis\Updater\ReleaseRollback;
use Inachis\Updater\SymlinkManagerInterface;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

final class ReleaseRollbackTest extends TestCase
{
    private string $tempDirectory;

    protected function setUp(): void
    {
        $this->tempDirectory = sys_get_temp_dir()
            .'/inachis_release_test_'.uniqid('', true);

        mkdir($this->tempDirectory.'/releases', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDirectory);
    }

    public function testAvailableRollbacksReturnsSortedPreviousReleases(): void
    {
        mkdir($this->tempDirectory.'/releases/20260101000000-1.0.0');
        mkdir($this->tempDirectory.'/releases/20260201000000-1.1.0');

        symlink(
            $this->tempDirectory.'/releases/20260201000000-1.1.0',
            $this->tempDirectory.'/current'
        );

        $rollback = new ReleaseRollback(
            new ReleaseLocator($this->tempDirectory),
            $this->createMock(SymlinkManagerInterface::class),
            $this->createMock(MaintenanceManager::class),
        );

        $releases = $rollback->availableRollbacks();

        self::assertCount(1, $releases);

        self::assertSame(
            '1.0.0',
            $releases[0]->version
        );
    }

    public function testAvailableRollbacksReturnsEmptyWhenDirectoryMissing(): void
    {
        $locator = new ReleaseLocator($this->tempDirectory);

        rmdir($locator->releasesDirectory());

        $rollback = new ReleaseRollback(
            $locator,
            $this->createMock(SymlinkManagerInterface::class),
            $this->createMock(MaintenanceManager::class),
        );

        self::assertSame(
            [],
            $rollback->availableRollbacks()
        );
    }

    public function testRollbackUsesSpecifiedRelease(): void
    {
        $target = $this->tempDirectory.'/releases/20260101000000-1.0.0';

        mkdir($target, 0775, true);

        $symlinkManager = $this->createMock(
            SymlinkManagerInterface::class
        );

        $symlinkManager
            ->expects($this->once())
            ->method('switchCurrent')
            ->with(
                $this->tempDirectory.'/current',
                $target,
            );

        $maintenance = $this->createMock(
            MaintenanceManager::class
        );

        $maintenance
            ->expects($this->once())
            ->method('enable');

        $maintenance
            ->expects($this->once())
            ->method('disable');

        $rollback = new ReleaseRollback(
            new ReleaseLocator($this->tempDirectory),
            $symlinkManager,
            $maintenance,
        );

        $release = new ReleaseInstance(
            identifier: '20260101000000-1.0.0',
            version: '1.0.0',
            path: $target,
        );

        $result = $rollback->rollback($release);

        self::assertSame(
            $release,
            $result
        );
    }

    public function testRollbackThrowsWhenTargetMissing(): void
    {
        $rollback = new ReleaseRollback(
            new ReleaseLocator($this->tempDirectory),
            $this->createMock(SymlinkManagerInterface::class),
            $this->createMock(MaintenanceManager::class),
        );

        $this->expectException(\RuntimeException::class);

        $rollback->rollback(
            new ReleaseInstance(
                identifier: 'missing',
                version: '1.0.0',
                path: $this->tempDirectory.'/missing',
            )
        );
    }

    public function testRollbackThrowsWhenNoPreviousReleaseExists(): void
    {
        $rollback = new ReleaseRollback(
            new ReleaseLocator($this->tempDirectory),
            $this->createMock(SymlinkManagerInterface::class),
            $this->createMock(MaintenanceManager::class),
        );

        $this->expectException(\RuntimeException::class);

        $rollback->rollback();
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $path = $directory.'/'.$file;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
