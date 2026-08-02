<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Service\File;

use Inachis\Service\File\Base64EncodeFile;
use PHPUnit\Framework\TestCase;

final class Base64EncodeFileTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        if (!is_dir('tests/tmp')) {
            mkdir('tests/tmp', 0777, true);
        }

        $this->tempFile = 'tests/tmp/test_image.png';

        $imageContent = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAn8B9Un8D2MAAAAASUVORK5CYII=',
            true
        );

        $this->assertNotFalse($imageContent);

        file_put_contents($this->tempFile, $imageContent);
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempFile)) {
            unlink($this->tempFile);
        }

        @rmdir('tests/tmp');
    }

    public function testEncodeReturnsBase64DataUri(): void
    {
        $result = Base64EncodeFile::encode($this->tempFile);

        $this->assertStringStartsWith('data:image/png;base64,', $result);

        $base64 = substr($result, strpos($result, ',') + 1);

        $this->assertSame(
            file_get_contents($this->tempFile),
            base64_decode($base64, true)
        );
    }

    public function testEncodeMissingFileReturnsEmptyString(): void
    {
        $this->assertSame(
            '',
            Base64EncodeFile::encode('tests/tmp/does-not-exist.png')
        );
    }

    public function testEncodeBlocksRelativePathTraversal(): void
    {
        $this->assertSame(
            '',
            Base64EncodeFile::encode('../../../../../../etc/hosts')
        );
    }

    public function testEncodeBlocksAbsolutePathTraversal(): void
    {
        $this->assertSame(
            '',
            Base64EncodeFile::encode('/../../../../../../etc/hosts')
        );
    }

    public function testEncodeUsesFilenameExtensionForMimeType(): void
    {
        $jpg = 'tests/tmp/test_image.jpg';

        copy($this->tempFile, $jpg);

        try {
            $result = Base64EncodeFile::encode($jpg);

            $this->assertStringStartsWith(
                'data:image/jpg;base64,',
                $result
            );
        } finally {
            @unlink($jpg);
        }
    }
}
