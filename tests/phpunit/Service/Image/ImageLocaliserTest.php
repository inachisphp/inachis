<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Service\Image;

use App\Service\Image\ImageLocaliser;
use PHPUnit\Framework\MockObject\MockClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Exception;

class ImageLocaliserTest extends TestCase
{
    private $filesystem;
    private $localiser;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->localiser = new ImageLocaliser($this->filesystem, '/fake/public/imgs');
    }

    public function testCreatesDirectoryIfNotExists(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('exists')->with('/fake/path')->willReturn(false);
        $filesystem->expects($this->once())->method('mkdir')->with('/fake/path', 0777);

        new ImageLocaliser($filesystem, '/fake/path');
    }

    /**
     * @throws Exception
     */
    public function testDownloadExceptionIfStreamFails(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Failed to move file/');
        $result = $this->localiser->downloadToLocal('invalid://url');
    }

    /**
     * @throws Exception
     */
    public function testDownloadExceptionIfFileEmpty(): void
    {
        if (!function_exists('mime_content_type')) {
            $this->markTestSkipped('mime_content_type not available');
        }
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Failed to move file/');
        $tmpFile = sys_get_temp_dir() . '/test/testfile2.txt';
        $this->prepareTestFile($tmpFile, '');
        $this->localiser->downloadToLocal('file://' . $tmpFile);
        @unlink($tmpFile);
    }


    /**
     * @throws Exception
     */
    public function testAddsExtensionWhenMissing(): void
    {
        if (!function_exists('mime_content_type')) {
            $this->markTestSkipped('mime_content_type not available');
        }
        $tmpFile = sys_get_temp_dir() . '/test/testfile_noext';
        $this->prepareTestFile($tmpFile, 'abc123');
        $renameCalls = [];
        $this->filesystem->method('rename')
            ->willReturnCallback(function ($from, $to) use (&$renameCalls) {
                $renameCalls[] = [$from, $to];
                return true;
            });
        $this->assertEquals('/imgs/testfile_noext.plain', $this->localiser->downloadToLocal('file://' . $tmpFile));
        @unlink($tmpFile);
    }

    public function testThrowsExceptionIfRenameFails(): void
    {
        if (!function_exists('mime_content_type')) {
            $this->markTestSkipped('mime_content_type not available');
        }
        $tmpFile = sys_get_temp_dir() . '/test/testfile2.txt';
        $this->prepareTestFile($tmpFile, 'abc123');
        $this->filesystem
            ->expects($this->once())
            ->method('rename')
            ->willThrowException(new Exception('rename failed'));
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Failed to move file/');
        $this->localiser->downloadToLocal('file://' . $tmpFile);
        @unlink($tmpFile);
    }

    private function prepareTestFile(string $filename, string $contents = ''): void
    {
        if (!is_dir(sys_get_temp_dir() . '/test')) {
            mkdir(sys_get_temp_dir() . '/test');
        }
        file_put_contents($filename, $contents);
    }

    protected function tearDown(): void
    {
        @rmdir(sys_get_temp_dir() . '/test');
        @unlink(sys_get_temp_dir() . '/test/testfile_noext');
        @unlink(sys_get_temp_dir() . '/test/testfile2.txt');
    }
}
