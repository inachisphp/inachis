<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Util;

class TextCleaner
{
    public const REMOVE_BLOCKQUOTE_CONTENT = 1;
    public const REMOVE_IMAGE_ALT = 2;
    public const NORMALISE_WHITESPACE = 4;

    /**
     * @param string|null $text
     * @param int $options
     * @return string
     */
    public static function strip(?string $text, int $options = 0): string
    {
        // Remove HTML
        $text = strip_tags((string) $text);

        // Remove Markdown images ![alt](url)
        if ($options & self::REMOVE_IMAGE_ALT) {
            $text = preg_replace('/!\[(.*?)\]\(.*?\)/', '', $text);
        } else {
            $text = preg_replace('/!\[(.*?)\]\(.*?\)/', '$1', $text);
        }

        // Remove Markdown links [text](url)
        $text = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $text);

        // Remove blockquotes and list markers
        if ($options & self::REMOVE_BLOCKQUOTE_CONTENT) {
            $text = preg_replace('/^ {0,3}>\s?.*?\n/m', '', $text);
        } else {
            $text = preg_replace('/^ {0,3}>\s?/m', '', $text);
        }
        $text = preg_replace('/^ {0,3}([-*+]|\d+\.)\s+/m', '', $text);

        // Remove bold/italic markers (**text**, *text*, __text__, _text_)
        $text = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $text);
        $text = preg_replace('/([*_])(.*?)/', '$2', $text);

        // Remove inline code and code fences (`code` or ```code```)
        $text = preg_replace('/```\s+(.+?)\s+```/s', '$1', $text);
        $text = preg_replace('/`(.+?)`/s', '$1', $text);

        // Remove horizontal rules
        $text = preg_replace('/^\s{0,3}([-*_]\s?){3,}$/m', '', $text);

        if ($options & self::NORMALISE_WHITESPACE) {
            $text = preg_replace('/\n{2,}/', "\n", $text);
            $text = preg_replace('/ {2,}/', ' ', $text);
        }

        return trim($text);
    }
}
