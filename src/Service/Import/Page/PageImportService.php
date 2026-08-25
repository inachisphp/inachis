<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Url;
use Inachis\Entity\User\User;
use Inachis\Enum\EditorialStatus;
use Inachis\Model\Import\ImportOptionsDto;
use Inachis\Model\Page\PageExportDto;
use Inachis\Repository\Content\UrlRepository;

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
        private ?UrlRepository $urlRepository = null,
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
        $urlRepo = $this->urlRepository ?? $this->entityManager->getRepository(Url::class);

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
                    if (empty($categoryDto->path)) {
                        continue;
                    }
                    $wasCreatedBefore = $this->categoryService->wasCreated($categoryDto->path);
                    $category = $this->categoryService->findOrCreateByPath($categoryDto->path, true);

                    if ($category) {
                        $page->addCategory($category);
                        if (!$wasCreatedBefore && $this->categoryService->wasCreated($categoryDto->path)) {
                            ++$result->categoriesCreated;
                        }
                    }
                }

                foreach ($dto->tags as $tagDto) {
                    if (empty($tagDto->title)) {
                        continue;
                    }
                    $wasCreatedBefore = $this->tagService->wasCreated($tagDto->title);
                    $tag = $this->tagService->findOrCreateByTitle($tagDto->title, true);

                    if ($tag) {
                        $page->addTag($tag);
                        if (!$wasCreatedBefore && $this->tagService->wasCreated($tagDto->title)) {
                            ++$result->tagsCreated;
                        }
                    }
                }

                foreach ($dto->urls as $urlDto) {
                    if (empty($urlDto->path)) {
                        continue;
                    }
                    $uniquePath = $urlRepo->getUniqueUrl($urlDto->path);
                    new Url($page, $uniquePath, $urlDto->default);
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
