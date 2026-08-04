<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Import\Series;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Repository\Content\PageRepository;

/**
 * Service for importing series and linking pages.
 */
final class SeriesImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageRepository $pageRepository,
    ) {
    }

    /**
     * Import series from DTOs.
     *
     * @param list<SeriesExportDto|null> $seriesDtos
     */
    public function import(iterable $seriesDtos): SeriesImportResult
    {
        $result = new SeriesImportResult();
        $this->entityManager->beginTransaction();

        try {
            foreach ($seriesDtos as $seriesDto) {
                if (!$seriesDto instanceof SeriesExportDto) {
                    throw new \InvalidArgumentException('All items must be SeriesExportDto');
                }

                $series = new Series();
                $series->setTitle($seriesDto->title);
                $series->setSubTitle($seriesDto->subTitle);
                $series->setUrl($seriesDto->url); // TODO: need to check if URL is already in use and generate a new one if so
                $series->setDescription($seriesDto->description);
                $series->setFirstDate(new \DateTimeImmutable($seriesDto->firstDate ?: ''));
                $series->setLastDate(new \DateTimeImmutable($seriesDto->lastDate ?: ''));
                $series->setVisible(false);

                // Link pages by title
                foreach ($seriesDto->items as $pageTitle) {
                    /** @var Page|null $page */
                    $page = $this->pageRepository->findOneBy(['title' => $pageTitle]);

                    if ($page) {
                        $series->addItem($page);
                        ++$result->pagesLinked;
                    } else {
                        $result->warnings[] = sprintf(
                            'Series "%s": page "%s" not found and could not be linked.',
                            $seriesDto->title,
                            $pageTitle,
                        );
                    }
                }

                $this->entityManager->persist($series);
                ++$result->seriesImported;
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $result->warnings[] = 'Import failed: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * Maps the imported data to DTOs.
     *
     * @param array<array{
     *     title?: string,
     *     subTitle?: string,
     *     url?: string,
     *     description?: string,
     *     firstDate?: string,
     *     lastDate?: string,
     *     visible?: bool,
     *     items?: list<string>
     * }> $data
     *
     * @return SeriesExportDto[]
     */
    public function mapToDto(array $data): array
    {
        $dtos = [];

        foreach ($data as $series) {
            $dto = new SeriesExportDto();
            $dto->title = $series['title'] ?? '';
            $dto->subTitle = $series['subTitle'] ?? null;
            $dto->url = $series['url'] ?? '';
            $dto->description = $series['description'] ?? null;
            $dto->firstDate = $series['firstDate'] ?? null;
            $dto->lastDate = $series['lastDate'] ?? null;
            $dto->visible = $series['visible'] ?? true;
            $dto->items = $series['items'] ?? [];

            $dtos[] = $dto;
        }

        return $dtos;
    }
}
