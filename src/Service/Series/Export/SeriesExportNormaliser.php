<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Series\Export;

use Inachis\Entity\Series;
use Inachis\Model\Series\SeriesExportDto;

final class SeriesExportNormaliser
{
    public function normalize(Series $series): SeriesExportDto
    {
        $dto = new SeriesExportDto();

        $dto->title = $series->getTitle();
        $dto->subTitle = $series->getSubTitle();
        $dto->url = $series->getUrl();
        $dto->description = $series->getDescription();

        $dto->firstDate = $series->getFirstDate()?->format('Y-m-d');
        $dto->lastDate  = $series->getLastDate()?->format('Y-m-d');

        $dto->visibility = $series->getVisibility();

        foreach ($series->getItems() ?? [] as $page) {
            $dto->items[] = $page->getTitle();
        }

        return $dto;
    }
}