<?php

namespace App\Utils;

class ContentRevisionCompare
{
    /**
     * @param $page
     * @param $revision
     * @return bool
     */
    public function doesPageMatchRevision($page, $revision): bool
    {
        return
            $revision->getContent() === $page->getContent() &&
            $revision->getTitle() === $page->getTitle() &&
            $revision->getSubTitle() === $page->getSubTitle();
    }
}
