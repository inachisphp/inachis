<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Service\Image;

use Symfony\Component\Filesystem\Filesystem;
use Exception;

class ImageLocaliser
{
    protected string $publicImgPath = '';
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem, string $publicImgPath)
    {
        $this->filesystem = $filesystem;
        $this->publicImgPath = $publicImgPath;
        if (!$this->filesystem->exists($this->publicImgPath)) {
            $this->filesystem->mkdir($this->publicImgPath);
        }
    }

    /**
     * Download an image from a URL and move it to the local /public/imgs directory.
     *
     * @return string|null The relative web path (e.g. "/imgs/image.jpg"), or null if download failed.
     * @throws Exception
     */
    public function downloadToLocal(string $url): ?string
    {
        $filename = basename((string) parse_url($url, PHP_URL_PATH));
        $tmpPath = sys_get_temp_dir() . '/' . $filename;
        $finalPath = $this->publicImgPath . '/' . $filename;

        try {
            $stream = @fopen($url, 'r');
            if (!$stream) {
                throw new Exception('Could not open remote file');
            }
            $bytes = @file_put_contents($tmpPath, $stream);
            fclose($stream);
            if ($bytes === false || $bytes === 0) {
                throw new Exception('Resulting file was empty');
            }

            if (pathinfo($tmpPath, PATHINFO_EXTENSION) === '') {
                $extension = explode('/', mime_content_type($tmpPath))[1];
                $this->filesystem->rename($tmpPath, $tmpPath . '.' . $extension, true);
                $filename .= '.' . $extension;
                $tmpPath .= '.' . $extension;
                $finalPath .= '.' . $extension;
            }
            $this->filesystem->rename($tmpPath, $finalPath, true);

            return '/imgs/' . $filename;
        } catch (Exception $e) {
            throw new Exception(sprintf('Failed to move file to %s', $finalPath), 0, $e);
        }
    }
}
