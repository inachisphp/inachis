<?php

namespace App\Util;

class ReadingTime
{
    private const WORDS_PER_MINUTE = 238;

    /**
     * @param string|null $text
     * @param int|null $wordCount
     * @param int|null $wpm
     * @return int
     */
    public static function getReadingTime(?string $text, ?int $wordCount = 0, ?int $wpm = self::WORDS_PER_MINUTE): int
    {
        return ceil( ($wordCount > 0 ? $wordCount : self::getWordCount($text)) / $wpm);
    }

    /**
     * @param string|null $text
     * @return int
     */
    public static function getWordCount(?string $text): int
    {
        // @todo: consider removing markdown from string also
        return str_word_count(strip_tags((string) $text));
    }

    /**
     * @param string|null $text
     * @param int|null $wpm
     * @return array
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
