<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Export\Series;

use Inachis\Entity\Content\Series;
use Inachis\Model\Series\SeriesExportDto;

/**
 * Normalises a series for export.
 */
final class SeriesExportNormaliser
{
    /**
     * Normalises a series for export.
     *
     * @param Series $series The series to normalise.
     * @return SeriesExportDto The normalised series.
     */
    public function normalise(Series $series): SeriesExportDto
    {
        $dto = new SeriesExportDto();

        $dto->title = $series->getTitle() ?? '';
        $dto->subTitle = $series->getSubTitle();
        $dto->url = $series->getUrl() ?? '';
        $dto->description = $series->getDescription();

        $dto->firstDate = $series->getFirstDate()?->format('Y-m-d');
        $dto->lastDate  = $series->getLastDate()?->format('Y-m-d');

        $dto->visible = $series->isVisible();

        foreach ($series->getItems() as $page) {
            $dto->items[] = $page->getTitle();
        }

        return $dto;
    }
}