<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Content\Page;

use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Url;

/**
 * Manager class for applying a Url to a Page
 */
class UrlManager
{
    /**
     * Apply specified Url to the provided {@link Page}
     *
     * @param Page $page
     * @param string $newUrl
     */
    public function apply(Page $page, ?string $newUrl): void
    {
        if (!$newUrl) {
            return;
        }

        $found = false;

        foreach ($page->getUrls() as $url) {
            if ($url->getLink() !== $newUrl) {
                $url->setDefault(false);
                continue;
            }

            $found = true;
        }

        if (!$found) {
            $page->addUrl(new Url($page, $newUrl));
        }
    }
}