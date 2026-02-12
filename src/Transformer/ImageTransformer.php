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
use ImagickPixel;

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
        return extension_loaded('imagick') && !empty(Imagick::queryformats('HEI*'));
    }

    /**
     * Check if AVIF is supported
     *
     * @return bool
     */
    public function isAVIFSupported(): bool
    {
        return extension_loaded('imagick') && !empty(Imagick::queryFormats('AVIF'));
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
     * Detect if image has alpha/transparency channel
     *
     * @param Imagick $imagick
     * @return bool
     */
    protected function hasTransparency(Imagick $imagick): bool
    {
        return (bool) $imagick->getImageAlphaChannel();
    }

    /**
     * Convert HEIC to JPEG
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @param int $quality
     * @param int $maxWidth
     * @param int $maxHeight
     * @return void
     * @throws \ImagickException
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
        $imagick->autoOrient();

        if ($maxWidth || $maxHeight) {
            $imagick->thumbnailImage($maxWidth ?? 0, $maxHeight ?? 0, true, true);
        }

        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality($quality);
        $imagick->stripImage();
        $imagick->writeImage($destinationPath);

        $imagick->clear();
        $imagick->destroy();
    }

    /**
     * Optimises the image by resizing to fit, and adjusting compression. Will convert to
     * WebP or AVIF format if available. It will also strip metadata from the image, and
     * preserve any alpha channel.
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $quality
     * @return void
     * @throws ImagickException
     */
    public function optimiseImage(
        string $sourcePath,
        string $destinationPath,
        int $maxWidth = 1920,
        int $maxHeight = 1920,
        int $quality = 85,
    ): void {
        $imagick = $this->createImagick();
        $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256);
        $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MAP, 256);
        $imagick->readImage($sourcePath);
        $imagick->autoOrient();

        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();
        if ($width > $maxWidth || $height > $maxHeight) {
            $imagick->thumbnailImage($maxWidth, $maxHeight, true, true);
        }

        $hasAlpha = $this->hasTransparency($imagick);

        $format = 'webp';
        if (!$hasAlpha && $this->isAVIFSupported()) {
            $format = 'avif';
        }
        if ($hasAlpha) {
            $imagick->setImageBackgroundColor(new ImagickPixel('transparent'));
        }
        $imagick->setImageFormat($format);

        if ($format === 'webp') {
            $imagick->setImageCompressionQuality($quality);
        } else {
            $imagick->setOption('avif:quality', (string) $quality);
        }

        $imagick->stripImage();
        $imagick->writeImage($destinationPath);

        $imagick->clear();
        $imagick->destroy();
    }
}