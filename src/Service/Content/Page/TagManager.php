<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Content\Page;

use Inachis\Entity\Content\Page;
use Inachis\Repository\Content\TagRepository;
use Ramsey\Uuid\Uuid;

/**
 * Manager class for applying tags to a Page
 */
class TagManager
{
    /**
     * Constructor for TagManager
     *
     * @param TagRepository $tagRepository
     */
    public function __construct(
        private TagRepository $tagRepository
    ) {}

    /**
     * Apply specified tags to the provided {@link Page}
     *
     * @param Page $page
     * @param string $rawTags
     */
    public function apply(Page $page, string $rawTags): void
    {
        $page->removeTags();

        $tags = array_filter(array_map(
            'trim',
            explode(',', $rawTags)
        ));

        foreach ($tags as $tagValue) {
            $tag = Uuid::isValid($tagValue)
                ? $this->tagRepository->find($tagValue)
                : $this->tagRepository->getOrCreate($tagValue);

            if ($tag) {
                $page->addTag($tag);
            }
        }
    }
}
