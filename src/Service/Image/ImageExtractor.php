<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Image;

/**
 * Service for extracting images from content
 */
class ImageExtractor
{
    /**
     * @var string The regex for matching markdown images
     */
    private const MARKDOWN_IMAGE = '/\!\[[^\]]*]\((https?:\/\/(?:[^\)]+))\)/';

    /**
     * Extracts images from content
     * 
     * @param string $content The content to extract images from
     * @return array<string> An array of image URLs
     */
    public function extractFromContent(string $content): array
    {
        preg_match_all(self::MARKDOWN_IMAGE, $content, $matches);
        return $matches[1] ?? [];
    }
}
