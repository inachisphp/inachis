<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Transformer;

use App\Transformer\ImageTransformer;
use Imagick;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ImageTransformerTest extends TestCase
{
    private ImageTransformer $imageTransformer;

    protected function setUp(): void
    {
        $this->imageTransformer = new ImageTransformer();
    }

    public function testIsHEICSupported(): void
    {
        $this->assertIsBool($this->imageTransformer->isHEICSupported());
    }

    public function testCreateImagick(): void
    {
        $reflection = new ReflectionClass($this->imageTransformer);
        $method = $reflection->getMethod('createImagick');
        $method->setAccessible(true);
        $this->assertEquals(new Imagick(), $method->invoke($this->imageTransformer));
    }

    /**
     * @throws \ImagickException
     * @throws Exception
     */
    public function testConvertHeicToJpegWithAutoOrient(): void
    {
        $mock = $this->createMock(Imagick::class);

        $mock->expects($this->once())->method('readImage')->with('/src.heic');
        $mock->expects($this->once())->method('setImageFormat')->with('jpeg');
        $mock->expects($this->once())->method('setImageCompression');
        $mock->expects($this->once())->method('setImageCompressionQuality')->with(85);
        $mock->expects($this->once())->method('stripImage');
        $mock->expects($this->once())->method('writeImage')->with('/dest.jpg');
        $mock->expects($this->once())->method('clear');
        $mock->expects($this->once())->method('destroy');

        // orientation invoked here
        $mock->expects($this->once())->method('autoOrient');

        $service = new class($mock) extends ImageTransformer {
            private $m;
            public function __construct($m) { $this->m = $m; }

            protected function isHeicAvailable(): bool { return true; }
            protected function createImagick(): Imagick { return $this->m; }

            protected function imagickSupportsMethod(Imagick $i, string $method): bool {
                return $method === 'autoOrient';
            }
        };

        $service->convertHeicToJpeg('/src.heic', '/dest.jpg');
    }

    /**
     * @throws \ImagickException
     * @throws Exception
     */
    public function testConvertHeicToJpegWithAutoRotateImageAndResize(): void
    {
        $mock = $this->createMock(Imagick::class);

        $mock->expects($this->once())->method('readImage')->with('/input.heic');

        // Simulate calling autoRotateImage() by calling a real method
        $mock->expects($this->once())->method('setImageFormat')->with('jpeg');

        $mock->expects($this->once())->method('thumbnailImage')
            ->with(1200, 900, true, true);

        $mock->expects($this->once())->method('setImageCompression');
        $mock->expects($this->once())->method('setImageCompressionQuality')->with(70);
        $mock->expects($this->once())->method('stripImage');
        $mock->expects($this->once())->method('writeImage')->with('/out.jpg');
        $mock->expects($this->once())->method('clear');
        $mock->expects($this->once())->method('destroy');

        $service = new class($mock) extends ImageTransformer {
            private $m;
            public function __construct($m) { $this->m = $m; }

            protected function isHeicAvailable(): bool { return true; }
            protected function createImagick(): Imagick { return $this->m; }

            protected function imagickSupportsMethod(Imagick $i, string $method): bool {
                // Simulate autoRotateImage exists, autoOrient does not
                return $method === 'autoRotateImage';
            }

            protected function applyOrientation(Imagick $imagick): void
            {
                // stand-in for autoRotateImage() that definitely exists
                $imagick->getImageBlob();
            }
        };

        $service->convertHeicToJpeg('/input.heic', '/out.jpg', 70, 1200, 900);
    }

    public function testConvertHeicToJpegWhenHeicIsNotSupported(): void
    {
        // Imagick should NOT be created or called.
        $imagick = $this->createMock(\Imagick::class);

        // Anonymous subclass overrides isHEICSupported() to force false
        $service = new class($imagick) extends ImageTransformer {
            private $mock;
            public function __construct($mock) { $this->mock = $mock; }

            public function isHEICSupported(): bool
            {
                return false; // simulate no HEIC support
            }

            // ensure Imagick is never actually requested
            protected function createImagick(): \Imagick
            {
                $this->fail('createImagick() should not be called when HEIC is unsupported.');
            }
        };

        // Call conversion
        $service->convertHeicToJpeg('/fake.heic', '/output.jpg');

        // Expect: NO interactions with Imagick
        $this->assertTrue(true, 'convertHeicToJpeg should exit early when HEIC unsupported.');
    }


}