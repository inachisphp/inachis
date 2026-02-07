<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Series;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\{Page,Series};
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Repository\PageRepository;
use Inachis\Service\Import\Series\SeriesImportResult;
use DateTime;
use InvalidArgumentException;

/**
 * Service for importing series and linking pages.
 */
final class SeriesImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageRepository $pageRepository,
    ) {}

    /**
     * Import series from DTOs.
     *
     * @param SeriesExportDto[] $seriesDtos
     * @return SeriesImportResult
     */
    public function import(iterable $seriesDtos): SeriesImportResult
    {
        $result = new SeriesImportResult();
        $this->entityManager->beginTransaction();

        try {
            foreach ($seriesDtos as $seriesDto) {
                if (!$seriesDto instanceof SeriesExportDto) {
                    throw new InvalidArgumentException('All items must be SeriesExportDto');
                }

                $series = new Series();
                $series->setTitle($seriesDto->title);
                $series->setSubTitle($seriesDto->subTitle);
                $series->setUrl($seriesDto->url); //@todo need to check if URL is already in use and generate a new one if so
                $series->setDescription($seriesDto->description);
                $series->setFirstDate(new DateTime($seriesDto->firstDate));
                $series->setLastDate(new DateTime($seriesDto->lastDate));
                $series->setVisibility(Series::PRIVATE);

                // Link pages by title
                foreach ($seriesDto->items as $pageTitle) {
                    /** @var Page|null $page */
                    $page = $this->pageRepository->findOneBy(['title' => $pageTitle]);

                    if ($page) {
                        $series->addItem($page);
                        $result->pagesLinked++;
                    } else {
                        $result->warnings[] = sprintf(
                            'Series "%s": page "%s" not found and could not be linked.',
                            $seriesDto->title,
                            $pageTitle
                        );
                    }
                }

                $this->entityManager->persist($series);
                $result->seriesImported++;
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $result->warnings[] = 'Import failed: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Maps the imported data to DTOs.
     *
     * @param array $data
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
            $dto->visibility = $series['visibility'] ?? true;
            $dto->items = $series['items'] ?? [];

            $dtos[] = $dto;
        }

        return $dtos;
    }
}