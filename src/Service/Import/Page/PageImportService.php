<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Page;
use Inachis\Entity\User\User;
use Inachis\Enum\EditorialStatus;
use Inachis\Model\Import\ImportOptionsDto;
use Inachis\Model\Page\PageExportDto;

/**
 * Service for importing pages.
 */
final class PageImportService
{
    /**
     * Constructor for PageImportService.
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryImportService $categoryService,
        private TagImportService $tagService,
    ) {
    }

    /**
     * Imports the given pages.
     *
     * @param iterable<object> $pageDtos the pages to import
     * @param User             $author   the author of the pages
     * @param ImportOptionsDto $options  the import options
     *
     * @return PageImportResult the result of the import
     */
    public function import(
        iterable $pageDtos,
        User $author,
        ImportOptionsDto $options,
    ): PageImportResult {
        $result = new PageImportResult();
        $this->entityManager->beginTransaction();

        try {
            foreach ($pageDtos as $dto) {
                if (!$dto instanceof PageExportDto) {
                    throw new \InvalidArgumentException('All items must be PageExportDto');
                }

                $page = new Page(
                    title: $dto->title,
                    content: $dto->content ?? '',
                    author: $author,
                    type: $dto->type ?? Page::TYPE_POST,
                );

                $page->setStatus(EditorialStatus::from($dto->status));
                $page->setVisible($dto->visible ?? true);
                $page->setAllowComments($dto->allowComments ?? false);
                $page->setLanguage($dto->language ?? '');
                $page->setTimezone($dto->timezone ?? 'UTC');

                if ($dto->postDate && $options->overridePostDates) {
                    $page->setPostDate(new \DateTimeImmutable($dto->postDate));
                }

                foreach ($dto->categories as $categoryDto) {
                    $category = $this->categoryService->findOrCreateByPath(
                        $categoryDto->path,
                        $options->createMissingCategories,
                    );

                    if ($category) {
                        $page->addCategory($category);

                        // Count creation if it was newly created
                        if ($options->createMissingCategories) {
                            ++$result->categoriesCreated;
                        }
                    } else {
                        $result->warnings[] = "Category not found: {$categoryDto->path}";
                    }
                }

                foreach ($dto->tags as $tagDto) {
                    $tag = $this->tagService->findOrCreateByTitle(
                        $tagDto->title,
                        $options->createMissingTags,
                    );

                    if ($tag) {
                        $page->addTag($tag);

                        if ($options->createMissingTags) {
                            ++$result->tagsCreated;
                        }
                    } else {
                        $result->warnings[] = "Tag not found: {$tagDto->title}";
                    }
                }

                $this->entityManager->persist($page);
                ++$result->pagesImported;
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $result->warnings[] = 'Import failed: '.$e->getMessage();
        }

        return $result;
    }
}
