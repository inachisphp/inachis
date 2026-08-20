<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Parser;

/**
 * Converts an array representation of a {@link Page} to markdown.
 */
final class ArrayToMarkdown
{
    /**
     * Converts an array representation of a {@link Page} to markdown.
     *
     * Row 0 - title
     * Row 1 - subtitle / post date
     * Row 2 - postdate / category
     * Row 3 - Category / null
     * Row 4+ - Post content
     *
     * @param array<string, string|null>|array{} $post The array representation of a {@link Page}
     *
     * @return string The markdown representation of a {@link Page}
     */
    public static function parse(array $post): string
    {
        $lines = [];

        if (!empty($post['title'])) {
            $lines[] = '# '.$post['title'];
        }

        if (!empty($post['subTitle'])) {
            $lines[] = '## '.$post['subTitle'];
        }

        if (!empty($post['content'])) {
            $lines[] = '';
            $lines[] = '';
            $lines[] = $post['content'];
        }

        return implode(PHP_EOL, $lines);
    }
}
