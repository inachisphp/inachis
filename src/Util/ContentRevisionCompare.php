<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Util;

use Inachis\Entity\Page;
use Inachis\Entity\Revision;

/**
 * Compare page content to revision
 */
class ContentRevisionCompare
{
    /**
     * Check if page matches revision
     *
     * @param Page $page
     * @param Revision $revision
     * @return bool
     */
    public static function doesPageMatchRevision(Page $page, Revision $revision): bool
    {
        return
            $revision->getContent() === $page->getContent() &&
            $revision->getTitle() === $page->getTitle() &&
            $revision->getSubTitle() === $page->getSubTitle();
    }
}
