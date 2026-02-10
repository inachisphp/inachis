<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Transformer;

use Imagick;
use ImagickException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Transform images
 */
class ImageTransformer
{
    /**
     * Check if HEIC is supported
     *
     * @return bool
     */
    public function isHEICSupported(): bool
    {
        return extension_loaded('imagick') && !empty(\Imagick::queryformats('HEI*'));
    }

    /**
     * Create Imagick instance
     *
     * @return Imagick
     */
    protected function createImagick(): Imagick
    {
        return new Imagick();
    }

    /**
     * Check if Imagick supports a specific method
     *
     * @param Imagick $imagick
     * @param string $method
     * @return bool
     */
    protected function imagickSupportsMethod(Imagick $imagick, string $method): bool
    {
        return method_exists($imagick, $method);
    }

    /**
     * Apply orientation
     *
     * @param Imagick $imagick
     * @return void
     */
    protected function applyOrientation(Imagick $imagick): void
    {
        if ($this->imagickSupportsMethod($imagick, 'autoOrient')) {
            $imagick->autoOrient();
        }
    }

    /**
     * Convert HEIC to JPEG
     *
     * @throws ImagickException
     */
    public function convertHeicToJpeg(
        string $sourcePath,
        string $destinationPath,
        int $quality = 85,
        ?int $maxWidth = 0,
        ?int $maxHeight = 0
    ): void {
        if (!$this->isHEICSupported()) {
            return;
        }
        $imagick = $this->createImagick();
        $imagick->readImage($sourcePath);
        $this->applyOrientation($imagick);

        if ($maxWidth !== 0 || $maxHeight !== 0) {
            $imagick->thumbnailImage(
                $maxWidth ?? 0,
                $maxHeight ?? 0,
                true,
                true
            );
        }

        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality($quality);
        $imagick->stripImage();
        $imagick->writeImage($destinationPath);

        $imagick->clear();
        $imagick->destroy();
    }
}