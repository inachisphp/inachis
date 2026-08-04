<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content\Page;

use Inachis\Entity\Content\Page;
use Inachis\Repository\Content\TagRepository;
use Ramsey\Uuid\Uuid;

/**
 * Manager class for applying tags to a Page.
 */
class TagManager
{
    /**
     * Constructor for TagManager.
     */
    public function __construct(
        private TagRepository $tagRepository,
    ) {
    }

    /**
     * Apply specified tags to the provided {@link Page}.
     */
    public function apply(Page $page, string $rawTags): void
    {
        $page->removeTags();

        $tags = array_filter(array_map(
            'trim',
            explode(',', $rawTags),
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
