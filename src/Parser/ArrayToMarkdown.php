<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Parser;

/**
 * Converts an array representation of a {@link Page} to markdown
 */
final class ArrayToMarkdown
{
    /**
     * Converts an array representation of a {@link Page} to markdown
     * 
     * Row 0 - title
     * Row 1 - subtitle / post date
     * Row 2 - postdate / category
     * Row 3 - Category / null
     * Row 4+ - Post content
     * 
     * @param array<string> $post The array representation of a {@link Page}
     * @return string The markdown representation of a {@link Page}
     */
    public static function parse(array $post): string
    {
        $markdown = '';

        if (!empty($post['title'])) {
            $markdown .= '# ' . $post['title'] . PHP_EOL;
        }
        if (!empty($post['subTitle'])) {
            $markdown .= '## ' . $post['subTitle'] . PHP_EOL;
        }
        if (!empty($post['postDate'])) {
            $markdown .= $post['postDate'] . PHP_EOL;
        }
        if (!empty($post['categories']) && !empty($post['categories'][0]['fullPath'])) {
            $markdown .= $post['categories'][0]['fullPath'] . PHP_EOL;
        }
        if (!empty($post['content'])) {
            $markdown .= PHP_EOL . PHP_EOL . $post['content'];
        }

        return $markdown;
    }
}
