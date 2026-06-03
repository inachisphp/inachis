<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\Traits;

trait UrlPathTrait
{
    /**
     * Returns the URL path for sitemap generation.
     * This simply prefixes the stored link with a leading slash.
     * 
     * @return string The URL path to be used in the sitemap, always starting with a slash. If the link is empty, returns '/'.
     */
    public function getPath(): string
    {
        $path = $this->link ?? '';
        if ($path === '') {
            return '/';
        }
        return '/' . ltrim($path, '/');
    }
}
