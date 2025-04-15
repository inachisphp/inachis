<?php

namespace App\Utils;

use App\Entity\Url;

class UrlNormaliser
{
    /**
     * Turns a given string into an SEO-friendly URL
     * @param string $title The string to turn into an SEO friendly short URL
     * @param int|null    $limit The maximum number of characters to allow;
     *                   the default is defined by URL::DEFAULT_URL_SIZE_LIMIT
     *                   is defined by URL::DEFAULT_URL_SIZE_LIMIT
     * @return string The generated SEO-friendly URL
     */
    public static function toUri(string $title, ?int $limit = Url::DEFAULT_URL_SIZE_LIMIT): string
    {
        $title = trim(
            preg_replace(
                [
                    '/&/',
                    '/[_\s\x{00a0}]+/',
                    '/[^a-z0-9\-]+/i'
                ],
                [
                    'and',
                    '-',
                    ''
                ],
                mb_strtolower($title)
            ),
            '-'
        );
        if (mb_strlen($title) > $limit) {
            $title = mb_substr($title, 0, $limit);
        }
        return trim($title, '-');
    }

    /**
     * Returns a string containing a "short URL" from the given URI
     * @param string $uri The URL to parse and obtain the short URL for
     * @return string
     */
    public static function fromUri(string $uri) : string
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        if (substr($uri, -1) == '/') {
            $uri = substr($uri, 0, -1);
        }
        $uri = explode('/', $uri);
        return end($uri);
    }
}
