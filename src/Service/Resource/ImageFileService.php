<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Service\Resource;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageFileService
{
    /**
     * Create a hash of the uploaded image
     * @param UploadedFile $file
     * @return string
     */
    public function createChecksum(UploadedFile $file): string
    {
        $context = hash_init('sha256');
        $fp = fopen($file->getRealPath(), 'rb');
        while (!feof($fp)) {
            hash_update($context, fread($fp, 8192));
        }
        fclose($fp);

        return hash_final($context);
    }

    /**
     * Uses PHP function getimagesize to get the dimensions of the uploaded image
     * @param UploadedFile $file
     * @return array
     */
    public function getImageDimensions(UploadedFile $file): array
    {
        return getimagesize($file->getRealPath());
    }

    /**
     * @todo handle optimise image which reduces to Image::WARNING_SIZE max and 85% compression if JPEG
     */
    public function optimise(UploadedFile $file): UploadedFile
    {
        return $file;
    }

    /**
     * @todo if HEIC, and HEIC supported, convert to JPEG
     * @param UploadedFile $file
     * @return UploadedFile
     */
    public function convertHEICToJPEG(UploadedFile $file): UploadedFile
    {
        return $file;
    }
}