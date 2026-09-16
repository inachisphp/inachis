<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Series;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Entity\User\User;
use Inachis\Model\Import\ImportOptionsDto;
use Inachis\Model\Page\PageExportDto;
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Repository\Content\PageRepository;
use Inachis\Service\Import\Page\PageImportMapper;
use Inachis\Service\Import\Page\PageImportService;

/**
 * Service for importing series and linking pages.
 */
final class SeriesImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageRepository $pageRepository,
        private ?PageImportService $pageImportService = null,
        private ?PageImportMapper $pageImportMapper = null,
    ) {
    }

    /**
     * Import series from DTOs.
     *
     * @param iterable<SeriesExportDto> $seriesDtos
     */
    public function import(
        iterable $seriesDtos,
        ?User $author = null,
        ?ImportOptionsDto $options = null,
    ): SeriesImportResult {
        $result = new SeriesImportResult();
        $options ??= new ImportOptionsDto();

        $this->entityManager->beginTransaction();

        try {
            foreach ($seriesDtos as $seriesDto) {
                if (!$seriesDto instanceof SeriesExportDto) {
                    throw new \InvalidArgumentException('All items must be instances of ' . SeriesExportDto::class);
                }

                $series = new Series();
                $series->setTitle($seriesDto->title);
                $series->setSubTitle($seriesDto->subTitle);
                $series->setUrl($seriesDto->url);
                $series->setDescription($seriesDto->description);
                $series->setFirstDate($seriesDto->firstDate ? new \DateTimeImmutable($seriesDto->firstDate) : null);
                $series->setLastDate($seriesDto->lastDate ? new \DateTimeImmutable($seriesDto->lastDate) : null);
                $series->setVisible($seriesDto->visible ?? false);

                foreach ($seriesDto->items as $item) {
                    if (is_string($item)) {
                        $page = $this->pageRepository->findOneBy(['title' => $item]);

                        if ($page instanceof Page) {
                            $series->addItem($page);
                            ++$result->pagesLinked;
                        } else {
                            $result->warnings[] = sprintf(
                                'Series "%s": Referenced page "%s" not found.',
                                $seriesDto->title,
                                $item
                            );
                        }
                    } elseif ($item instanceof PageExportDto) {
                        $existingPage = $this->findMatchingPage($item);

                        if ($existingPage instanceof Page) {
                            $series->addItem($existingPage);
                            ++$result->pagesLinked;
                        } elseif (null !== $this->pageImportService) {
                            $pageAuthor = $author ?? $this->entityManager->getRepository(User::class)->findOneBy([]);

                            if ($pageAuthor instanceof User) {
                                $pageResult = $this->pageImportService->import([$item], $pageAuthor, $options);
                                $newPage = $this->findMatchingPage($item) ?? $this->pageRepository->findOneBy(['title' => $item->title]);

                                if ($newPage instanceof Page) {
                                    $series->addItem($newPage);
                                    ++$result->pagesLinked;
                                } else {
                                    $result->warnings = array_merge($result->warnings, $pageResult->warnings);
                                }
                            }
                        }
                    }
                }

                $this->entityManager->persist($series);
                ++$result->seriesImported;
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $result->warnings[] = 'Series import failed: ' . $e->getMessage();
        }

        return $result;
    }

    private function findMatchingPage(PageExportDto $pageDto): ?Page
    {
        /** @var Page[] $candidates */
        $candidates = $this->pageRepository->findBy(['title' => $pageDto->title]);

        foreach ($candidates as $candidate) {
            if ($candidate->getSubTitle() !== $pageDto->subTitle) {
                continue;
            }

            if (!empty($pageDto->urls)) {
                $dtoUrls = array_map(static fn ($u) => $u->path, $pageDto->urls);
                $matchUrl = false;

                foreach ($candidate->getUrls() as $url) {
                    if (in_array($url->getLink(), $dtoUrls, true)) {
                        $matchUrl = true;
                        break;
                    }
                }

                if (!$matchUrl) {
                    continue;
                }
            }

            return $candidate;
        }

        return null;
    }

    /**
     * Maps array raw data to DTOs.
     *
     * @param list<array<string, mixed>> $data
     * @return list<SeriesExportDto>
     */
    public function mapToDto(array $data): array
    {
        $dtos = [];
        $mapper = $this->pageImportMapper ?? new PageImportMapper($this->entityManager);

        foreach ($data as $series) {
            $dto = new SeriesExportDto();
            $dto->title = (string) ($series['title'] ?? '');
            $dto->subTitle = isset($series['subTitle']) ? (string) $series['subTitle'] : null;
            $dto->url = (string) ($series['url'] ?? '');
            $dto->description = isset($series['description']) ? (string) $series['description'] : null;
            $dto->firstDate = isset($series['firstDate']) ? (string) $series['firstDate'] : null;
            $dto->lastDate = isset($series['lastDate']) ? (string) $series['lastDate'] : null;
            $dto->visible = (bool) ($series['visible'] ?? true);

            $items = is_array($series['items'] ?? null) ? $series['items'] : [];
            foreach ($items as $item) {
                if (is_array($item)) {
                    $dto->items[] = $mapper->mapItemToDto($item);
                } elseif (is_scalar($item)) {
                    $dto->items[] = (string) $item;
                }
            }

            $dtos[] = $dto;
        }

        return $dtos;
    }
}
