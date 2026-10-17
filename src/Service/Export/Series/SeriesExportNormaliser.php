<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Export\Series;

use Inachis\Entity\Content\Series;
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Service\Export\Page\PageExportNormaliser;

/**
 * Normalises a series for export.
 */
final class SeriesExportNormaliser
{
    public function __construct(
        private ?PageExportNormaliser $pageNormaliser = null,
    ) {
    }

    /**
     * Normalises a series for export.
     *
     * @param Series $series the series to normalise
     *
     * @return SeriesExportDto the normalised series
     */
    public function normalise(Series $series, bool $includeFullPages = false): SeriesExportDto
    {
        $dto = new SeriesExportDto();

        $dto->title = $series->getTitle();
        $dto->subTitle = $series->getSubTitle();
        $dto->url = $series->getUrl();
        $dto->description = $series->getDescription();

        $dto->firstDate = $series->getFirstDate()?->format('Y-m-d');
        $dto->lastDate = $series->getLastDate()?->format('Y-m-d');

        $dto->visible = $series->isVisible();

        $pageNormaliser = $this->pageNormaliser ?? new PageExportNormaliser();

        foreach ($series->getItems() as $page) {
            if ($includeFullPages) {
                $dto->items[] = $pageNormaliser->normalise($page);
            } else {
                $dto->items[] = $page->getTitle();
            }
        }

        return $dto;
    }
}
