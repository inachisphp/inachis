<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Resource;

use Inachis\Entity\Image;
use Inachis\Transformer\ImageTransformer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 *
 */
readonly class ImageFileService
{
    public function __construct(
        private ImageTransformer $transformer
    ) {}

    /**
     * Create a hash of the uploaded image
     * @param UploadedFile $file
     * @return string
     */
    public function createChecksum(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw new \RuntimeException('Unable to determine file path.');
        }
        $hash = hash_file('sha256', $path);
        if ($hash === false) {
            throw new \RuntimeException('Unable to generate checksum.');
        }
        return $hash;
    }

    /**
     * Uses PHP function getimagesize to get the dimensions of the uploaded image
     * @param UploadedFile $file
     * @return array<int|string, int|string>|false
     */
    public function getImageDimensions(UploadedFile $file): array|false
    {
        return getimagesize($file->getRealPath());
    }

    /**
     * Optimise image: resize, compress, convert to WebP/AVIF
     *
     */
    public function optimise(UploadedFile $file): UploadedFile
    {
        if (!extension_loaded('imagick')) {
            return $file;
        }

        $file = $this->convertHEICToJPEG($file);

        $sourcePath = $file->getRealPath();
        if ($sourcePath === false) {
            throw new \RuntimeException('Unable to determine file path.');
        }

        $destinationPath = tempnam(sys_get_temp_dir(), 'opt_');
        if ($destinationPath === false) {
            throw new \RuntimeException('Unable to create temp file.');
        }

        $maxWidth = $maxHeight = Image::WARNING_DIMENSIONS;
        $this->transformer->optimiseImage(
            $sourcePath,
            $destinationPath,
            $maxWidth,
            $maxHeight
        );

        $extension = strtolower(pathinfo($destinationPath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['webp', 'avif'], true)) {
            $extension = '.webp';
            rename($destinationPath, $destinationPath . $extension);
            $destinationPath .= $extension;
        }

        return new UploadedFile(
            $destinationPath,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . $extension,
            mime_content_type($destinationPath) ?: null,
            null,
            true
        );
    }

    /**
     * Convert HEIC to JPEG if needed
     *
     * @param UploadedFile $file
     * @return UploadedFile
     */
    public function convertHEICToJPEG(UploadedFile $file): UploadedFile
    {
        if (!$this->transformer->isHEICSupported()) {
            return $file;
        }
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['heic', 'heif'], true)) {
            return $file;
        }
        $mime = $file->getMimeType();
        if (!in_array($mime, ['image/heic', 'image/heif'], true)) {
            return $file;
        }

        $sourcePath = $file->getRealPath();
        if ($sourcePath === false) {
            throw new \RuntimeException('Unable to determine file path.');
        }

        $destinationPath = tempnam(sys_get_temp_dir(), 'heic_') . '.jpg';

        try {
            $this->transformer->convertHeicToJpeg(
                $sourcePath,
                $destinationPath,
                85
            );
        } catch (\ImagickException $e) {
            unlink($destinationPath);
            throw new \RuntimeException('HEIC conversion failed.', 0, $e);
        }

        return new UploadedFile(
            $destinationPath,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg',
            'image/jpeg',
            null,
            true,
        );
    }
}