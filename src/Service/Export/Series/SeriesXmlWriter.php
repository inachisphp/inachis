<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Export\Series;

use Inachis\Model\Series\SeriesExportDto;
use Inachis\Service\Export\AbstractXmlExportWriter;

/**
 * XML writer for series.
 */
final class SeriesXmlWriter extends AbstractXmlExportWriter
{
    /**
     * Checks if the writer supports the given format.
     */
    public function supports(string $format): bool
    {
        return 'xml' === $format;
    }

    /**
     * Checks if the writer supports the given content domain.
     */
    public function supportsDomain(?string $domain): bool
    {
        return 'series' === $domain;
    }

    /**
     * The root node for the XML document.
     */
    protected function rootNodeName(): string
    {
        return 'seriesCollection';
    }

    /**
     * The item node for the XML document.
     */
    protected function itemNodeName(): string
    {
        return 'series';
    }

    /**
     * Writes the given series to XML format.
     *
     * @param SeriesExportDto $item
     */
    protected function writeItem(\SimpleXMLElement $xml, object $item): void
    {
        $this->optional($xml, 'title', $item->title);
        $this->optional($xml, 'subTitle', $item->subTitle);
        $this->optional($xml, 'description', $item->description);
        $xml->addChild('url', $item->url);
        $this->optional($xml, 'firstDate', $item->firstDate);
        $this->optional($xml, 'lastDate', $item->lastDate);
        $this->boolean($xml, 'visible', $item->visible, 'public', 'private');

        $items = $xml->addChild('items');
        foreach ($item->items as $title) {
            $items->addChild('item', $title);
        }
    }
}
