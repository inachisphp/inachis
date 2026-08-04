<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content;

/**
 * ReadingTime class.
 */
class ReadingTime
{
    /**
     * Words per minute.
     */
    private const WORDS_PER_MINUTE = 238;

    /**
     * Get reading time.
     */
    public static function getReadingTime(?string $text, ?int $wordCount = 0, ?int $wpm = self::WORDS_PER_MINUTE): float
    {
        return ceil(($wordCount > 0 ? $wordCount : self::getWordCount($text)) / $wpm);
    }

    /**
     * Get word count.
     */
    public static function getWordCount(?string $text): int
    {
        $text = TextCleaner::strip($text, TextCleaner::REMOVE_IMAGE_ALT | TextCleaner::NORMALISE_WHITESPACE);

        return str_word_count($text);
    }

    /**
     * Get word count and reading time.
     *
     * @return array<string, int|float>
     */
    public static function getWordCountAndReadingTime(?string $text, ?int $wpm = self::WORDS_PER_MINUTE): array
    {
        $wordCount = self::getWordCount($text);

        return [
            'readingTime' => self::getReadingTime($text, $wordCount, $wpm),
            'wordCount' => $wordCount,
        ];
    }
}
