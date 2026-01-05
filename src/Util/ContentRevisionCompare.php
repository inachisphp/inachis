<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Util;

class ContentRevisionCompare
{
    /**
     * @param $page
     * @param $revision
     * @return bool
     */
    public static function doesPageMatchRevision($page, $revision): bool
    {
        return
            $revision->getContent() === $page->getContent() &&
            $revision->getTitle() === $page->getTitle() &&
            $revision->getSubTitle() === $page->getSubTitle();
    }
}
