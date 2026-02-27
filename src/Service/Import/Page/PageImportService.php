<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Category;
use Inachis\Entity\Page;
use Inachis\Entity\Tag;
use Inachis\Entity\User;
use Inachis\Model\Import\ImportOptionsDto;
use Inachis\Model\Page\PageExportDto;
use Inachis\Service\Import\Page\PageImportResult;
use InvalidArgumentException;

/**
 * Service for importing pages.
 */
final class PageImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryImportService $categoryService,
        private TagImportService $tagService,
    ) {}

    /**
     * Imports the given pages.
     *
     * @param iterable $pageDtos The pages to import.
     * @param User $author The author of the pages.
     * @param ImportOptionsDto $options The import options.
     * @return PageImportResult The result of the import.
     */
    public function import(
        iterable $pageDtos,
        User $author,
        ImportOptionsDto $options
    ): PageImportResult {
        $result = new PageImportResult();
        $this->entityManager->beginTransaction();

        try {

            foreach ($pageDtos['pages'] as $dto) {
                if (!$dto instanceof PageExportDto) {
                    throw new InvalidArgumentException('All items must be PageExportDto');
                }

                $page = new Page(
                    title: $dto->title,
                    content: $dto->content ?? '',
                    author: $author,
                    type: $dto->type ?? Page::TYPE_POST
                );

                $page->setStatus($dto->status ?? Page::DRAFT);
                $page->setVisibility($dto->visibility ?? Page::PUBLIC);
                $page->setAllowComments($dto->allowComments ?? false);
                $page->setLanguage($dto->language ?? '');
                $page->setTimezone($dto->timezone ?? 'UTC');

                if ($dto->postDate && $options->overridePostDates) {
                    $page->setPostDate(new \DateTime($dto->postDate));
                }

                foreach ($dto->categories ?? [] as $categoryDto) {
                    $category = $this->categoryService->findOrCreateByPath(
                        $categoryDto->path,
                        $options->createMissingCategories
                    );

                    if ($category) {
                        $page->addCategory($category);

                        // Count creation if it was newly created
                        if ($options->createMissingCategories) {
                            $result->categoriesCreated++;
                        }
                    } else {
                        $result->warnings[] = "Category not found: {$categoryDto->path}";
                    }
                }

                foreach ($dto->tags ?? [] as $tagDto) {
                    $tag = $this->tagService->findOrCreateByTitle(
                        $tagDto->title,
                        $options->createMissingTags
                    );

                    if ($tag) {
                        $page->addTag($tag);

                        if ($options->createMissingTags) {
                            $result->tagsCreated++;
                        }
                    } else {
                        $result->warnings[] = "Tag not found: {$tagDto->title}";
                    }
                }

                // @todo add page URL

                $this->entityManager->persist($page);
                $result->pagesImported++;
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $result->warnings[] = "Import failed: " . $e->getMessage();
        }

        return $result;
    }
}
