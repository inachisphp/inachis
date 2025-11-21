<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Transformer;

use Imagick;
use ImagickException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageTransformer
{
    public function isHEICSupported(): bool
    {
        return extension_loaded('imagick') && !empty(\Imagick::queryformats('HEI*'));
    }

    /**
     * @return Imagick
     */
    protected function createImagick(): Imagick
    {
        return new Imagick();
    }

    protected function applyOrientation(Imagick $imagick): void
    {
        if ($this->imagickSupportsMethod($imagick, 'autoOrient')) {
            $imagick->autoOrient();
        } else {
            $imagick->autoRotateImage();
        }
    }

    /**
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