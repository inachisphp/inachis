<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export\Page;

use Inachis\Entity\Page;
use Inachis\Model\Page\PageExportDto;
use Inachis\Model\Page\CategoryPathDto;
use Inachis\Model\Page\TagDto;
use Inachis\Model\Page\UrlDto;

/**
 * Normalises a page for export.
 */
final class PageExportNormaliser
{
    /**
     * Normalises a page for export.
     *
     * @param Page $page The page to normalise.
     * @return PageExportDto The normalised page.
     */
    public function normalise(Page $page): PageExportDto
    {
        $dto = new PageExportDto();

        $dto->title = $page->getTitle() ?? '';
        $dto->subTitle = $page->getSubTitle();
        $dto->content = $page->getContent();
        $dto->type = $page->getType();
        $dto->status = $page->getStatus();
        $dto->visibility = $page->getVisibility();
        $dto->allowComments = $page->isAllowComments();
        $dto->language = $page->getLanguage();
        $dto->timezone = $page->getTimezone();
        $dto->postDate = $page->getPostDate()?->format('c');

        foreach ($page->getCategories() ?? [] as $category) {
            $catDto = new CategoryPathDto();
            $catDto->path = $category->getFullPath();
            $dto->categories[] = $catDto;
        }

        foreach ($page->getTags() ?? [] as $tag) {
            $tagDto = new TagDto();
            $tagDto->title = $tag->getTitle();
            $dto->tags[] = $tagDto;
        }

        foreach ($page->getUrls() ?? [] as $url) {
            if ($url->isDefault()) {
                $urlDto = new UrlDto();
                $urlDto->path = $url->getLink();
                $urlDto->default = $url->isDefault();
                $dto->urls[] = $urlDto;
                break;
            }
        }

        return $dto;
    }

    /**
     * Normalize multiple pages
     *
     * @param iterable $pages The pages to normalise.
     * @return array The normalised pages.
     */
    public function normaliseCollection(iterable $pages): array
    {
        $result = [];
        foreach ($pages as $page) {
            $result[] = $this->normalise($page);
        }
        return $result;
    }
}
