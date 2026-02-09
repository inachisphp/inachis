<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Series\Export;

use Inachis\Model\Series\SeriesExportDto;
use Inachis\Service\Export\AbstractXmlExportWriter;
use SimpleXMLElement;

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
        return $format === 'xml';
    }

    /**
     * Checks if the writer supports the given content domain.
     */
    public function supportsDomain(?string $domain): bool
    {
        return $domain === 'series';
    }

    /**
     * The root node for the XML document.
     *
     * @return string
     */
    protected function rootNodeName(): string
    {
        return 'seriesCollection';
    }

    /**
     * The item node for the XML document.
     *
     * @return string
     */
    protected function itemNodeName(): string
    {
        return 'series';
    }

    /**
     * Writes the given series to XML format.
     *
     * @param SimpleXMLElement $xml
     * @param SeriesExportDto $item
     */
    protected function writeItem(SimpleXMLElement $xml, object $item): void
    {
        $this->optional($xml, 'title', $item->title);
        $this->optional($xml, 'subTitle', $item->subTitle);
        $this->optional($xml, 'description', $item->description);
        $xml->addChild('url', $item->url);
        $this->optional($xml, 'firstDate', $item->firstDate);
        $this->optional($xml, 'lastDate', $item->lastDate);
        $this->boolean($xml, 'visibility', $item->visibility, 'public', 'private');

        $items = $xml->addChild('items');
        foreach ($item->items as $title) {
            $items->addChild('item', $title);
        }
    }
}
