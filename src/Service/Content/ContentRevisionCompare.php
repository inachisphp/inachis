<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content;

use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Revision;

/**
 * Compare page content to revision.
 */
class ContentRevisionCompare
{
    /**
     * Check if page matches revision.
     */
    public static function doesPageMatchRevision(Page $page, Revision $revision): bool
    {
        return
            $revision->getContent() === $page->getContent()
            && $revision->getTitle() === $page->getTitle()
            && $revision->getSubTitle() === $page->getSubTitle();
    }
}
