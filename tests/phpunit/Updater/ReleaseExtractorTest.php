<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater;

use Inachis\Updater\ReleaseExtractor;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ReleaseExtractorTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = sys_get_temp_dir().'/inachis-release-extractor-test-'.uniqid();

        mkdir($this->testDirectory, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDirectory);
    }

    public function testExtractsArchiveSuccessfully(): void
    {
        $archive = $this->createArchive([
            'index.php' => '<?php echo "hello";',
            'config/app.yaml' => 'test: true',
        ]);

        $destination = $this->testDirectory.'/release';

        $extractor = new ReleaseExtractor();

        $extractor->extract(
            $archive,
            $destination,
        );

        self::assertFileExists(
            $destination.'/index.php'
        );

        self::assertFileExists(
            $destination.'/config/app.yaml'
        );

        self::assertSame(
            '<?php echo "hello";',
            file_get_contents($destination.'/index.php')
        );
    }

    public function testCreatesMissingDestinationDirectory(): void
    {
        $archive = $this->createArchive([
            'test.txt' => 'contents',
        ]);

        $destination = $this->testDirectory.'/new-release';

        self::assertDirectoryDoesNotExist($destination);

        $extractor = new ReleaseExtractor();

        $extractor->extract(
            $archive,
            $destination,
        );

        self::assertFileExists(
            $destination.'/test.txt'
        );
    }

    public function testThrowsExceptionForInvalidArchive(): void
    {
        $archive = $this->testDirectory.'/invalid.zip';

        file_put_contents(
            $archive,
            'not a zip file'
        );

        $extractor = new ReleaseExtractor();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to open release archive');

        $extractor->extract(
            $archive,
            $this->testDirectory.'/release',
        );
    }

    public function testBlocksZipSlipAttack(): void
    {
        $archive = $this->createArchive([
            '../../outside.txt' => 'malicious',
        ]);

        $extractor = new ReleaseExtractor();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Zip slip attempt detected');

        $extractor->extract(
            $archive,
            $this->testDirectory.'/release',
        );

        self::assertFileDoesNotExist(
            $this->testDirectory.'/../outside.txt'
        );
    }

    private function createArchive(array $files): string
    {
        $archive = $this->testDirectory.'/archive-'.uniqid().'.zip';

        $zip = new ZipArchive();

        self::assertTrue(
            $zip->open($archive, ZipArchive::CREATE)
        );

        foreach ($files as $filename => $contents) {
            $zip->addFromString(
                $filename,
                $contents,
            );
        }

        $zip->close();

        return $archive;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (
            array_diff(
                scandir($directory) ?: [],
                ['.', '..']
            ) as $file
        ) {
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
