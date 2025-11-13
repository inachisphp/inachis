<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Service\Image;

class ImageExtractor
{
    private const MARKDOWN_IMAGE = '/\!\[[^\]]*]\((https?:\/\/(?:[^\)]+))\)/';

    public function extractFromContent(string $content): array
    {
        preg_match_all(self::MARKDOWN_IMAGE, $content, $matches);
        return $matches[1] ?? [];
    }
}
