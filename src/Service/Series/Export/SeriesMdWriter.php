<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Series\Export;

use Inachis\Model\Series\SeriesExportDto;

final class SeriesMdWriter
{
    public function write(SeriesExportDto $dto): string
    {
        $content = "---\n";
        $content .= "title: " . $dto->title . "\n";
        $content .= "subtitle: " . $dto->subTitle . "\n";
        $content .= "url: " . $dto->url . "\n";
        $content .= "description: " . $dto->description . "\n";
        $content .= "firstDate: " . $dto->firstDate . "\n";
        $content .= "lastDate: " . $dto->lastDate . "\n";
        $content .= "visibility: " . $dto->visibility . "\n";
        $content .= "items: " . implode(", ", $dto->items) . "\n";
        $content .= "---\n";

        return $content;
    }
}