<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Util;

use App\Util\Base64EncodeFile;
use PHPUnit\Framework\TestCase;

class Base64EncodeFileTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        if (!is_dir('tests/tmp')) {
            mkdir('tests/tmp');
        }
        $this->tempFile = 'tests/tmp/test_image.png';
        $imageContent = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAn8B9Un8D2MAAAAASUVORK5CYII='
        );
        file_put_contents($this->tempFile, $imageContent);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        @rmdir('test/tmp');
    }

    public function testEncodeReturnsBase64DataUri(): void
    {
        $result = Base64EncodeFile::encode($this->tempFile);
        $this->assertStringStartsWith('data:image/png;base64,', $result);

        $base64Part = substr($result, strpos($result, ',') + 1);
        $decodedContent = base64_decode($base64Part);
        $this->assertEquals(file_get_contents($this->tempFile), $decodedContent);
    }

    public function testEncodeMissingFile(): void
    {
        $this->assertEmpty(Base64EncodeFile::encode('tests/tmp/test_images.png'));
    }

    public function testEncodeProtectsPathTraversal(): void
    {
        $result = Base64EncodeFile::encode('env.local');
        $this->assertEmpty($result);
        $result = Base64EncodeFile::encode('../src/Kernel.php');
        $this->assertEmpty($result);
        $result = Base64EncodeFile::encode('/src/../../../../../../../etc/hosts');
        $this->assertEmpty($result);
    }
}