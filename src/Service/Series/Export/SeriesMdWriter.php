<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Series\Export;

use Inachis\Model\Series\SeriesExportDto;
use Inachis\Service\Export\ExportWriterInterface;

/**
 * Markdown writer for series.
 */
final class SeriesMdWriter implements ExportWriterInterface
{
    /**
     * Checks if the writer supports the given format.
     */
    public function supports(string $format): bool
    {
        return $format === 'md';
    }

    /**
     * Writes the given series to the specified format.
     */
    public function write(iterable $items): string
    {
        $output = '';

        foreach ($items as $item) {
            $output .= "---\n";
            $output .= "title: " . $item->title . "\n";
            $output .= "subtitle: " . $item->subTitle . "\n";
            $output .= "url: " . $item->url . "\n";
            $output .= "description: " . $item->description . "\n";
            $output .= "firstDate: " . $item->firstDate . "\n";
            $output .= "lastDate: " . $item->lastDate . "\n";
            $output .= "visibility: " . $item->visibility . "\n";
            $output .= "items: " . implode(", ", $item->items) . "\n";
            $output .= "---\n";
        }
        return $output;
    }
}