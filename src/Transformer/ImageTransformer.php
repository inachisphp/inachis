<?php
namespace App\Transformer;

class ImageTransformer
{
    public function isHEICSupported(): bool
    {
        return extension_loaded('imagick') && !empty(\Imagick::queryformats('HEI*'));
    }

    public function convertHeicToJpeg(UploadedFile $file)
    {
    }
}