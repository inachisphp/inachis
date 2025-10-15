<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Parser;

use App\Entity\Category;
use App\Entity\Page;
use Doctrine\Common\Persistence\ObjectManager;

final class ArrayToMarkdown
{
    /**
     * Row 0 - title
     * Row 1 - subtitle / post date
     * Row 2 - postdate / category
     * Row 3 - Category / null
     * Row 4+ - Post content
     * @param string[] $post
     * @return string
     */
    public static function parse(array $post) : string
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
            $markdown .=  $post['categories'][0]['fullPath'] . PHP_EOL;
        }
        if (!empty($post['content'])) {
            $markdown .= PHP_EOL . PHP_EOL . $post['content'];
        }

        return $markdown;
    }
}
