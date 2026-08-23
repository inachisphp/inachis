<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Image\Migration;

use Symfony\Component\Mime\MimeTypes;

class ImageProcessor
{
    private const WEBP_QUALITY = 80;

    /**
     * Compute true pixel checksum by hashing uncompressed raw RGB pixel data.
     */
    public function computePixelChecksum(string $filePath): string
    {
        if (!file_exists($filePath) || !is_file($filePath)) {
            return '';
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ('svg' === $ext) {
            return hash_file('sha256', $filePath) ?: '';
        }

        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick($filePath);
                $imagick->autoOrient();
                $imagick->stripImage();

                $w = $imagick->getImageWidth();
                $h = $imagick->getImageHeight();

                if ($w > 0 && $h > 0) {
                    $pixels = $imagick->exportImagePixels(0, 0, $w, $h, 'RGB', \Imagick::PIXEL_CHAR);
                    $hash = hash('sha256', pack('C*', ...$pixels));
                    $imagick->clear();
                    $imagick->destroy();

                    return $hash;
                }
                $imagick->clear();
                $imagick->destroy();
            } catch (\Throwable) {
                // Fallback to GD
            }
        }

        return $this->computePixelChecksumGd($filePath);
    }

    /**
     * GD raw pixel sampling fallback.
     */
    private function computePixelChecksumGd(string $filePath): string
    {
        $info = @getimagesize($filePath);
        if (false === $info) {
            return hash_file('sha256', $filePath) ?: '';
        }

        $mime = $info['mime'];
        $srcImage = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($filePath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($filePath) : false,
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($filePath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : false,
            default => false
        };

        if (false === $srcImage) {
            return hash_file('sha256', $filePath) ?: '';
        }

        $w = imagesx($srcImage);
        $h = imagesy($srcImage);
        $buffer = '';

        $stepX = max(1, (int) floor($w / 100));
        $stepY = max(1, (int) floor($h / 100));

        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                $rgb = imagecolorat($srcImage, $x, $y);
                if (false !== $rgb) {
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $buffer .= pack('CCC', $r, $g, $b);
                }
            }
        }

        imagedestroy($srcImage);

        return hash('sha256', $buffer);
    }

    /**
     * Resize image preserving aspect ratio and transparency, downscaling using Lanczos filter.
     */
    public function resizeImage(string $srcPath, string $dstPath, int $maxDimension): bool
    {
        if (!file_exists($srcPath)) {
            return false;
        }

        $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
        if ('svg' === $ext) {
            return copy($srcPath, $dstPath);
        }

        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick($srcPath);
                $imagick->autoOrient();

                $w = $imagick->getImageWidth();
                $h = $imagick->getImageHeight();

                if ($w > $maxDimension || $h > $maxDimension) {
                    $ratio = min($maxDimension / $w, $maxDimension / $h, 1.0);
                    $targetW = (int) round($w * $ratio);
                    $targetH = (int) round($h * $ratio);

                    if ($imagick->getImageAlphaChannel()) {
                        $imagick->setImageBackgroundColor(new \ImagickPixel('transparent'));
                    }

                    $imagick->resizeImage($targetW, $targetH, \Imagick::FILTER_LANCZOS, 1, true);
                    $imagick->stripImage();
                }

                $imagick->writeImage($dstPath);
                $imagick->clear();
                $imagick->destroy();

                return true;
            } catch (\Throwable) {
                // Fallback to GD
            }
        }

        return $this->resizeImageGd($srcPath, $dstPath, $maxDimension);
    }

    /**
     * GD image resize fallback with transparency support.
     */
    private function resizeImageGd(string $srcPath, string $dstPath, int $maxDimension): bool
    {
        $info = @getimagesize($srcPath);
        if (false === $info) {
            return false;
        }

        [$width, $height] = $info;
        if ($width <= 0 || $height <= 0) {
            return false;
        }

        if ($width <= $maxDimension && $height <= $maxDimension) {
            return copy($srcPath, $dstPath);
        }

        $ratio = min($maxDimension / $width, $maxDimension / $height, 1.0);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $mime = $info['mime'];
        $srcImage = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($srcPath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($srcPath) : false,
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($srcPath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false,
            default => false
        };

        if (false === $srcImage) {
            return false;
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        if (false === $dstImage) {
            imagedestroy($srcImage);

            return false;
        }

        if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
            if (false !== $transparent) {
                imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $result = match ($mime) {
            'image/jpeg' => imagejpeg($dstImage, $dstPath, 85),
            'image/png' => imagepng($dstImage, $dstPath, 8),
            'image/gif' => imagegif($dstImage, $dstPath),
            'image/webp' => imagewebp($dstImage, $dstPath, self::WEBP_QUALITY),
            default => false
        };

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $result;
    }

    /**
     * Convert image to WebP format, skipping SVGs and non-raster formats.
     */
    public function convertToWebp(string $srcPath, string $dstPath): bool
    {
        if (!file_exists($srcPath)) {
            return false;
        }

        $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
        if ('svg' === $ext) {
            return false;
        }

        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick($srcPath);
                $imagick->autoOrient();

                if ($imagick->getImageAlphaChannel()) {
                    $imagick->setImageBackgroundColor(new \ImagickPixel('transparent'));
                }

                $imagick->setImageFormat('webp');
                $imagick->setImageCompressionQuality(self::WEBP_QUALITY);
                $imagick->stripImage();
                $imagick->writeImage($dstPath);
                $imagick->clear();
                $imagick->destroy();

                return true;
            } catch (\Throwable) {
                // Fallback to GD
            }
        }

        if (!function_exists('imagewebp')) {
            return false;
        }

        $info = @getimagesize($srcPath);
        if (false === $info) {
            return false;
        }

        $mime = $info['mime'];
        $srcImage = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($srcPath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($srcPath) : false,
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($srcPath) : false,
            default => false
        };

        if (false === $srcImage) {
            return false;
        }

        if (in_array($mime, ['image/png', 'image/gif'], true)) {
            imagealphablending($srcImage, false);
            imagesavealpha($srcImage, true);
        }

        $result = imagewebp($srcImage, $dstPath, self::WEBP_QUALITY);
        imagedestroy($srcImage);

        return $result;
    }

    /**
     * Detect MIME type reliably using Symfony MimeTypes or finfo.
     */
    public function detectMimeType(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return 'application/octet-stream';
        }

        $mimeTypes = MimeTypes::getDefault();
        $mime = $mimeTypes->guessMimeType($filePath);
        if (!empty($mime)) {
            return $mime;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (false !== $finfo) {
                $mime = finfo_file($finfo, $filePath);
                finfo_close($finfo);
                if (!empty($mime)) {
                    return $mime;
                }
            }
        }

        return 'image/jpeg';
    }
}
