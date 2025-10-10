<?php

namespace App\Util;

class TextCleaner
{
    /**
     *
     */
    public const REMOVE_BLOCKQUOTE_CONTENT = 1;

    /**
     *
     */
    public const REMOVE_IMAGE_ALT = 2;

    public const NORMALISE_WHITESPACE = 4;

    /**
     * @param string|null $text
     * @param int $options
     * @return string
     */
    public function strip(?string $text, int $options = 0): string
    {
        // Remove HTML
        $text = strip_tags($text);

        // Remove Markdown links [text](url)
        $text = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $text);

        // Remove Markdown images ![alt](url)
        $text = preg_replace('/!\[(.*?)\]\(.*?\)/', '$1', $text);

        // Remove bold/italic markers (**text**, *text*, __text__, _text_)
        $text = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $text);
        $text = preg_replace('/([*_])(.*?)/', '$2', $text);

        // Remove inline code and code fences (`code` or ```code```)
        $text = preg_replace('/`{1,3}(.+?)`{1,3}/s', '$1', $text);

        // Remove blockquotes and list markers
        $text = preg_replace('/^\s{0,3}>\s?/m', '', $text);
        $text = preg_replace('/^\s{0,3}([-*+]|\d+\.)\s+/m', '', $text);

        // Remove horizontal rules
        $text = preg_replace('/^\s{0,3}([-*_]\s?){3,}$/m', '', $text);

        if ($options & self::NORMALISE_WHITESPACE) {
            $text = preg_replace('/\s+/', ' ', $text);
        }

        return trim($text);
    }
}