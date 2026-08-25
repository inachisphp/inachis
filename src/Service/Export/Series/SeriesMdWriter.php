<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Export\Series;

use Inachis\Model\Page\PageExportDto;
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Service\Export\ExportWriterInterface;

/**
 * Markdown writer for series.
 */
final class SeriesMdWriter implements ExportWriterInterface
{
    /**
     * Checks if the writer supports the given format.
     *
     * @param string $format the format to check
     *
     * @return bool true if the writer supports the format, false otherwise
     */
    public function supports(string $format): bool
    {
        return 'md' === $format;
    }

    /**
     * Checks if the writer supports the given content domain.
     *
     * @param string|null $domain the content domain to check
     *
     * @return bool true if the writer supports the domain, false otherwise
     */
    public function supportsDomain(?string $domain): bool
    {
        return 'series' === $domain;
    }

    /**
     * Writes the given series to the specified format.
     *
     * @param iterable<object> $items the series to write
     *
     * @return string the written series
     */
    public function write(iterable $items): string
    {
        $output = '';

        foreach ($items as $item) {
            if (!$item instanceof SeriesExportDto) {
                continue;
            }

            $output .= "---\n";
            $output .= 'title: '.$item->title."\n";
            $output .= 'subtitle: '.$item->subTitle."\n";
            $output .= 'url: '.$item->url."\n";
            $output .= 'description: '.$item->description."\n";
            $output .= 'firstDate: '.$item->firstDate."\n";
            $output .= 'lastDate: '.$item->lastDate."\n";
            $output .= 'visible: '.($item->visible ? 'true' : 'false')."\n";

            $itemTitles = [];
            $fullPages = [];
            foreach ($item->items as $pageItem) {
                if ($pageItem instanceof PageExportDto) {
                    $itemTitles[] = $pageItem->title;
                    $fullPages[] = $pageItem;
                } else {
                    $itemTitles[] = (string) $pageItem;
                }
            }
            $output .= 'items: '.implode(', ', $itemTitles)."\n";
            $output .= "---\n";

            if (!empty($fullPages)) {
                foreach ($fullPages as $p) {
                    $output .= "\n## Page: ".$p->title."\n";
                    if ($p->subTitle) {
                        $output .= 'Subtitle: '.$p->subTitle."\n";
                    }
                    if (!empty($p->urls)) {
                        $output .= 'URL: '.$p->urls[0]->path."\n";
                    }
                    $output .= "\n".($p->content ?? '')."\n";
                }
            }
        }

        return $output;
    }
}
