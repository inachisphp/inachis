<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater;

use Inachis\Updater\ReleaseCleaner;
use Inachis\Updater\ReleaseLocator;
use PHPUnit\Framework\TestCase;

class ReleaseCleanerTest extends TestCase
{
    private string $tempDir;
    private string $releasesDir;
    private string $currentLink;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/inachis_cleaner_test_' . uniqid('', true);
        $this->releasesDir = $this->tempDir . '/releases';
        $this->currentLink = $this->tempDir . '/current';

        mkdir($this->releasesDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTempDirRecursive($this->tempDir);
        parent::tearDown();
    }

    public function testPruneReturnsEmptyArrayWhenReleasesDirectoryDoesNotExist(): void
    {
        // Use a path where 'releases' directory does not exist
        $locator = new ReleaseLocator($this->tempDir . '/non_existent');
        $cleaner = new ReleaseCleaner($locator);

        $this->assertSame([], $cleaner->prune(3));
    }

    public function testPruneDoesNothingWhenReleasesCountIsLessThanOrEqualToKeep(): void
    {
        $this->createReleaseDir('20260101000000-v1.0.0');
        $this->createReleaseDir('20260102000000-v1.0.1');

        $locator = new ReleaseLocator($this->tempDir);
        $cleaner = new ReleaseCleaner($locator);

        $pruned = $cleaner->prune(3);

        $this->assertSame([], $pruned);
        $this->assertDirectoryExists($this->releasesDir . '/20260101000000-v1.0.0');
        $this->assertDirectoryExists($this->releasesDir . '/20260102000000-v1.0.1');
    }

    public function testPruneEnforcesMinimumKeepThresholdOfTwo(): void
    {
        $rel1 = $this->createReleaseDir('20260101000000-v1.0.0');
        $rel2 = $this->createReleaseDir('20260102000000-v1.0.1');
        $rel3 = $this->createReleaseDir('20260103000000-v1.0.2');

        $locator = new ReleaseLocator($this->tempDir);
        $cleaner = new ReleaseCleaner($locator);

        // Requesting keep = 1 should be overridden to max(2, 1) = 2
        $pruned = $cleaner->prune(1);

        $this->assertCount(1, $pruned);
        $this->assertSame([$rel1], $pruned);
        $this->assertDirectoryDoesNotExist($rel1);
        $this->assertDirectoryExists($rel2);
        $this->assertDirectoryExists($rel3);
    }

    public function testPruneRemovesOldReleasesAndKeepsNewest(): void
    {
        $rel1 = $this->createReleaseDir('20260101000000-v1.0.0');
        $rel2 = $this->createReleaseDir('20260102000000-v1.0.1');
        $rel3 = $this->createReleaseDir('20260103000000-v1.0.2');
        $rel4 = $this->createReleaseDir('20260104000000-v1.0.3');

        // Create nested directories, files, and symlinks inside old release
        mkdir($rel1 . '/config/sub', 0777, true);
        file_put_contents($rel1 . '/config/sub/app.json', '{}');

        $externalTarget = $this->tempDir . '/external_file.txt';
        file_put_contents($externalTarget, 'important data');
        symlink($externalTarget, $rel1 . '/external_link');

        $locator = new ReleaseLocator($this->tempDir);
        $cleaner = new ReleaseCleaner($locator);

        $pruned = $cleaner->prune(2);

        $this->assertCount(2, $pruned);
        $this->assertContains($rel1, $pruned);
        $this->assertContains($rel2, $pruned);

        $this->assertDirectoryDoesNotExist($rel1);
        $this->assertDirectoryDoesNotExist($rel2);
        $this->assertDirectoryExists($rel3);
        $this->assertDirectoryExists($rel4);

        // Ensure target outside release folder was not deleted via symlink
        $this->assertFileExists($externalTarget);
    }

    public function testPruneAlwaysPreservesActiveCurrentReleaseEvenIfOld(): void
    {
        $relOldest = $this->createReleaseDir('20260101000000-v1.0.0');
        $rel2 = $this->createReleaseDir('20260102000000-v1.0.1');
        $rel3 = $this->createReleaseDir('20260103000000-v1.0.2');
        $relNewest = $this->createReleaseDir('20260104000000-v1.0.3');

        // Point current symlink to the oldest release
        symlink($relOldest, $this->currentLink);

        $locator = new ReleaseLocator($this->tempDir);
        $cleaner = new ReleaseCleaner($locator);

        $pruned = $cleaner->prune(2);

        $this->assertSame([$rel2], $pruned);

        $this->assertDirectoryExists($relOldest);
        $this->assertDirectoryDoesNotExist($rel2);
        $this->assertDirectoryExists($rel3);
        $this->assertDirectoryExists($relNewest);
    }

    public function testPruneIgnoresStrayFilesAndSymlinksInReleasesDirectory(): void
    {
        $this->createReleaseDir('20260101000000-v1.0.0');
        $this->createReleaseDir('20260102000000-v1.0.1');

        file_put_contents($this->releasesDir . '/stray_file.txt', 'test');

        $locator = new ReleaseLocator($this->tempDir);
        $cleaner = new ReleaseCleaner($locator);

        $pruned = $cleaner->prune(2);

        $this->assertSame([], $pruned);
        $this->assertFileExists($this->releasesDir . '/stray_file.txt');
    }

    private function createReleaseDir(string $name): string
    {
        $path = $this->releasesDir . '/' . $name;
        mkdir($path, 0777, true);

        return $path;
    }

    private function removeTempDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeTempDirRecursive($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
