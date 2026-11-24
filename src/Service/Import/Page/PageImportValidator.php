<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Page;

use Inachis\Entity\Content\Category;
use Inachis\Model\Page\PageExportDto;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\TagRepository;
use Inachis\Repository\Content\UrlRepository;

class PageImportValidator
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
        private UrlRepository $urlRepository,
    ) {}

    /**
     * @param list<PageExportDto> $dtos
     * @return array<int, list<array{type: string, title: string}>>
     */
    public function validateAll(array $dtos): array
    {
        $warnings = [];

        foreach ($dtos as $index => $dto) {
            $itemWarnings = [];

            // Check URLs
            foreach ($dto->urls as $urlDto) {
                if (empty($urlDto->path)) {
                    continue;
                }

                $existingUrl = $this->urlRepository->findOneBy(['link' => $urlDto->path]);
                if ($existingUrl) {
                    $itemWarnings[] = [
                        'type' => 'url_exists',
                        'title' => 'URL "' . $urlDto->path . '" already exists',
                    ];
                } else {
                    $itemWarnings[] = [
                        'type' => 'url_new',
                        'title' => 'New URL: "' . $urlDto->path . '"',
                    ];
                }
            }

            // Check Categories by traversing the tree
            foreach ($dto->categories as $categoryDto) {
                if (!$this->categoryExistsByPath($categoryDto->path)) {
                    $itemWarnings[] = [
                        'type' => 'category_create',
                        'title' => 'Category "' . $categoryDto->path . '" will be created',
                    ];
                }
            }

            // Check Tags
            foreach ($dto->tags as $tagDto) {
                $existingTag = $this->tagRepository->findOneBy(['title' => $tagDto->title]);
                if (!$existingTag) {
                    $itemWarnings[] = [
                        'type' => 'tag_create',
                        'title' => 'Tag "' . $tagDto->title . '" will be created',
                    ];
                }
            }

            $warnings[$index] = $itemWarnings;
        }

        return $warnings;
    }

    /**
     * Traverses category parents to verify if a given full path exists.
     */
    private function categoryExistsByPath(string $path): bool
    {
        $parts = array_filter(explode('/', trim($path, '/')));
        if (empty($parts)) {
            return false;
        }

        $parent = null;
        foreach ($parts as $title) {
            $category = $this->categoryRepository->findOneBy([
                'title' => $title,
                'parent' => $parent,
            ]);

            if (!$category instanceof Category) {
                return false;
            }

            $parent = $category;
        }

        return true;
    }
}
